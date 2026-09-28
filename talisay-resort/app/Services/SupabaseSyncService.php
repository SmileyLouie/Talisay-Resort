<?php

namespace App\Services;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\IntegrationOutbox;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes authoritative MySQL changes into the separate Supabase client database.
 * The service-role key stays on this server. The mobile app never sees it.
 */
class SupabaseSyncService
{
    public function configured(): bool
    {
        return filled(config('integration.supabase_url'))
            && filled(config('integration.supabase_service_role'));
    }

    public function pushUnit(AccommodationUnit $unit): void
    {
        $this->send('units', 'mysql_id', $this->unitPayload($unit), 'unit', $unit->id, 'upsert');
    }

    public function deleteUnit(int $mysqlId): void
    {
        $this->send('units', null, ['mysql_id' => $mysqlId], 'unit', $mysqlId, 'delete');
    }

    public function pushBooking(Booking $booking): void
    {
        $booking->loadMissing(['user', 'payment', 'accommodationUnit']);
        if (!$booking->external_id || !$booking->user?->external_id) {
            return;
        }

        $payment = $booking->payment;
        $this->send('bookings', 'id', [
            'id' => $booking->external_id,
            'client_id' => $booking->user->external_id,
            'mysql_booking_id' => $booking->id,
            'mysql_unit_id' => $booking->accommodation_unit_id,
            'reference_no' => $booking->reference_no,
            'check_in' => optional($booking->check_in_date ?? $booking->booking_date)->toDateString(),
            'check_out' => $booking->checkOutDate()->toDateString(),
            'guests_count' => $booking->guests_count,
            'payment_method' => $payment->payment_channel ?? $payment->gateway ?? 'cash',
            'special_requests' => $booking->special_requests,
            'status' => $booking->status,
            'sync_status' => 'synced',
            'sync_error' => null,
            'total_amount' => $booking->total_amount,
            'updated_at' => now()->toIso8601String(),
        ], 'booking', $booking->id, 'upsert');

        if ($payment) {
            $this->send('payments', 'booking_id', [
                'booking_id' => $booking->external_id,
                'mysql_payment_id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->payment_channel ?? $payment->gateway,
                'status' => $payment->status,
                'updated_at' => now()->toIso8601String(),
            ], 'payment', $payment->id, 'upsert');
        }

        $this->pushNotification(
            $booking->user,
            'Reservation update',
            'Reservation '.$booking->reference_no.' is now '.str_replace('_', ' ', $booking->status).'.',
        );
    }

    public function pushReview(Review $review): void
    {
        if (!$review->external_id) {
            return;
        }

        $this->send('reviews', 'id', [
            'id' => $review->external_id,
            'mysql_review_id' => $review->id,
            'is_comment_blocked' => (bool) $review->is_comment_blocked,
            'sync_status' => 'synced',
        ], 'review', $review->id, 'upsert');
    }

    public function pushNotification(User $user, string $title, string $body): void
    {
        if (!$user->external_id) {
            return;
        }

        $this->send('notifications', null, [
            'client_id' => $user->external_id,
            'title' => $title,
            'body' => $body,
        ], 'notification', $user->id, 'insert');
    }

    public function retryPending(int $limit = 25): int
    {
        $rows = IntegrationOutbox::whereNull('processed_at')
            ->where('attempts', '<', 8)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $done = 0;
        foreach ($rows as $row) {
            try {
                $this->dispatch($row->entity, $row->action, $row->payload ?? []);
                $row->update(['processed_at' => now(), 'last_error' => null]);
                $done++;
            } catch (Throwable $e) {
                $row->update([
                    'attempts' => $row->attempts + 1,
                    'last_error' => $e->getMessage(),
                ]);
            }
        }

        return $done;
    }

    private function send(string $table, ?string $conflict, array $payload, string $entity, ?int $mysqlId, string $action): void
    {
        if (!$this->configured()) {
            return;
        }

        try {
            $this->dispatch($action === 'delete' ? 'unit' : $entity, $action, $payload + ['table' => $table, 'conflict' => $conflict]);
        } catch (Throwable $e) {
            IntegrationOutbox::create([
                'entity' => $entity,
                'mysql_id' => $mysqlId,
                'action' => $action,
                'payload' => $payload + ['table' => $table, 'conflict' => $conflict],
                'attempts' => 1,
                'last_error' => $e->getMessage(),
            ]);
            Log::warning('Supabase sync queued for retry', ['entity' => $entity, 'error' => $e->getMessage()]);
        }
    }

    private function dispatch(string $entity, string $action, array $payload): void
    {
        $table = $payload['table'] ?? match ($entity) {
            'unit' => 'units',
            'booking' => 'bookings',
            'payment' => 'payments',
            'review' => 'reviews',
            default => 'notifications',
        };
        $conflict = $payload['conflict'] ?? null;
        unset($payload['table'], $payload['conflict']);

        $base = rtrim((string) config('integration.supabase_url'), '/').'/rest/v1/'.$table;
        $request = Http::withHeaders($this->headers())
            ->timeout(8)
            ->acceptJson();

        if ($action === 'delete') {
            $response = $request->delete($base.'?mysql_id=eq.'.$payload['mysql_id']);
        } elseif ($action === 'insert' || $conflict === null) {
            $response = $request->post($base, $payload);
        } else {
            $response = $request
                ->withHeaders(['Prefer' => 'resolution=merge-duplicates,return=minimal'])
                ->post($base.'?on_conflict='.$conflict, $payload);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Supabase '.$table.' '.$action.' failed: '.$response->status().' '.$response->body());
        }
    }

    private function headers(): array
    {
        $key = (string) config('integration.supabase_service_role');

        return [
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
            'Content-Type' => 'application/json',
        ];
    }

    private function unitPayload(AccommodationUnit $unit): array
    {
        $images = collect($unit->images ?? [])
            ->map(fn ($path) => url('storage/'.$path))
            ->values()
            ->all();

        return [
            'mysql_id' => $unit->id,
            'unit_number' => $unit->unit_number,
            'unit_type' => $unit->unit_type,
            'variant' => $unit->variant,
            'max_occupancy' => $unit->max_occupancy,
            'price_per_night' => $unit->price_per_night,
            'description' => $unit->description,
            'amenities' => $unit->amenities ?? [],
            'images' => $images,
            'is_available' => (bool) $unit->is_available,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
