<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\BookingService;
use App\Services\SupabaseSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class MobileIntegrationController extends Controller
{
    public function __construct(
        private BookingService $bookings,
        private SupabaseSyncService $sync,
    ) {}

    public function upsertClient(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::withTrashed()->where('external_id', $data['external_id'])->first()
            ?? User::withTrashed()->where('email', $data['email'])->first();

        if ($user && $user->role !== 'tourist') {
            return response()->json([
                'message' => 'This email is already used by an admin or staff account.',
            ], 422);
        }

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->fill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $user->phone,
                'external_id' => $data['external_id'],
                'role' => 'tourist',
            ])->save();
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make(Str::password(40)),
                'role' => 'tourist',
                'external_id' => $data['external_id'],
                'is_active' => true,
                'account_status' => 'active',
            ]);
        }

        return response()->json([
            'mysql_user_id' => $user->id,
            'external_id' => $user->external_id,
        ]);
    }

    public function storeBooking(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'uuid'],
            'client_external_id' => ['required', 'uuid'],
            'mysql_unit_id' => ['required', 'integer'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:100'],
            'payment_method' => ['required', 'in:gcash,cash,paypal,card'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Booking::where('external_id', $data['external_id'])->first();
        if ($existing) {
            return response()->json($this->bookingPayload($existing));
        }

        $user = User::where('external_id', $data['client_external_id'])->where('role', 'tourist')->first();
        if (!$user) {
            return response()->json(['message' => 'Client profile has not been synchronized yet.'], 422);
        }

        $unit = AccommodationUnit::find($data['mysql_unit_id']);
        if (!$unit) {
            return response()->json(['message' => 'That room or cottage is no longer listed.'], 422);
        }

        try {
            $booking = $this->bookings->createRegular([
                'unit' => $unit,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests_count' => $data['guests_count'],
                'user_id' => $user->id,
                'guest_name_manual' => $user->name,
                'guest_contact_manual' => $user->phone ?: $user->email,
                'payment_method' => $data['payment_method'],
                'special_requests' => $data['special_requests'] ?? null,
                'booking_source' => 'mobile',
                'status' => Booking::STATUS_PENDING,
            ]);
            $booking->external_id = $data['external_id'];
            $booking->save();
        } catch (BookingConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'sync_status' => 'rejected',
            ], 409);
        } catch (QueryException $e) {
            $again = Booking::where('external_id', $data['external_id'])->first();
            if ($again) {
                return response()->json($this->bookingPayload($again));
            }
            throw $e;
        }

        return response()->json($this->bookingPayload($booking->fresh(['payment'])), 201);
    }

    public function cancelBooking(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'uuid'],
            'client_external_id' => ['required', 'uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = Booking::where('external_id', $data['external_id'])->first();
        $user = User::where('external_id', $data['client_external_id'])->first();

        if (!$booking || !$user || $booking->user_id !== $user->id) {
            return response()->json(['message' => 'Reservation was not found for this client.'], 404);
        }

        if ($booking->status !== Booking::STATUS_CANCELLED) {
            $this->bookings->cancel($booking, $data['reason'] ?? 'Cancelled from the mobile app.', 'booking_cancelled_mobile');
        }

        return response()->json($this->bookingPayload($booking->fresh(['payment'])));
    }

    public function storeReview(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'uuid'],
            'client_external_id' => ['required', 'uuid'],
            'booking_external_id' => ['required', 'uuid'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $existing = Review::where('external_id', $data['external_id'])->first();
        if ($existing) {
            return response()->json(['mysql_review_id' => $existing->id, 'sync_status' => 'synced']);
        }

        $user = User::where('external_id', $data['client_external_id'])->where('role', 'tourist')->first();
        $booking = Booking::where('external_id', $data['booking_external_id'])->first();

        if (!$user || !$booking || $booking->user_id !== $user->id) {
            return response()->json(['message' => 'That stay cannot be reviewed by this client.'], 422);
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return response()->json(['message' => 'This stay already has a review.'], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'is_approved' => true,
            'external_id' => $data['external_id'],
        ]);

        return response()->json([
            'mysql_review_id' => $review->id,
            'sync_status' => 'synced',
        ], 201);
    }

    public function catalog()
    {
        $units = AccommodationUnit::orderBy('sort_order')->orderBy('unit_number')->get()
            ->map(fn (AccommodationUnit $unit) => [
                'mysql_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'unit_type' => $unit->unit_type,
                'variant' => $unit->variant,
                'max_occupancy' => $unit->max_occupancy,
                'price_per_night' => $unit->price_per_night,
                'description' => $unit->description,
                'amenities' => $unit->amenities ?? [],
                'images' => collect($unit->images ?? [])->map(fn ($path) => url('storage/'.$path))->values(),
                'is_available' => (bool) $unit->is_available,
            ]);

        return response()->json(['units' => $units]);
    }

    public function recommend(Request $request)
    {
        $data = $request->validate([
            'guests' => ['required', 'integer', 'min:1', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date'],
            'unit_type' => ['nullable', 'in:room,cottage'],
        ]);

        $query = AccommodationUnit::query()->where('is_available', true)->where('max_occupancy', '>=', $data['guests']);
        if (!empty($data['unit_type'])) {
            $query->where('unit_type', $data['unit_type']);
        }
        if (isset($data['budget'])) {
            $query->where('price_per_night', '<=', $data['budget']);
        }

        $matches = [];
        foreach ($query->orderBy('price_per_night')->get() as $unit) {
            if (!empty($data['check_in']) && !empty($data['check_out'])) {
                try {
                    $this->bookings->checkStay($unit, $data['check_in'], $data['check_out'], (int) $data['guests']);
                } catch (BookingConflictException) {
                    continue;
                }
            }
            $matches[] = [
                'mysql_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'unit_type' => $unit->unit_type,
                'variant' => $unit->variant,
                'max_occupancy' => $unit->max_occupancy,
                'price_per_night' => $unit->price_per_night,
                'description' => $unit->description,
            ];
        }

        return response()->json(['recommendations' => $matches]);
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'client_external_id' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array'],
        ]);

        $user = User::where('external_id', $data['client_external_id'])->where('role', 'tourist')->first();
        if (!$user) {
            return response()->json(['message' => 'Client profile has not been synchronized yet.'], 422);
        }

        $request->merge([
            'message' => $data['message'],
            'history' => $data['history'] ?? [],
        ]);
        Auth::setUser($user);

        return app(ChatbotController::class)->message($request);
    }

    private function bookingPayload(Booking $booking): array
    {
        $booking->loadMissing('payment');

        return [
            'mysql_booking_id' => $booking->id,
            'external_id' => $booking->external_id,
            'reference_no' => $booking->reference_no,
            'status' => $booking->status,
            'total_amount' => $booking->total_amount,
            'payment_status' => $booking->payment?->status,
            'sync_status' => 'synced',
        ];
    }
}
