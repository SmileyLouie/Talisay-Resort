<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integration.token' => 'test-integration-token']);
    }

    public function test_integration_rejects_missing_token(): void
    {
        $this->postJson('/api/integration/clients', [
            'external_id' => (string) Str::uuid(),
            'name' => 'Ana Cruz',
            'email' => 'ana@example.com',
        ])->assertStatus(401);
    }

    public function test_client_booking_is_authoritative_in_mysql_and_idempotent(): void
    {
        $externalUser = (string) Str::uuid();
        $this->withTokenHeader()->postJson('/api/integration/clients', [
            'external_id' => $externalUser,
            'name' => 'Ana Cruz',
            'email' => 'ana@example.com',
            'phone' => '09170000000',
        ])->assertOk();

        $unit = AccommodationUnit::create([
            'unit_number' => 'Cottage 03',
            'unit_type' => 'cottage',
            'variant' => 'normal',
            'max_occupancy' => 6,
            'price_per_night' => 2500,
            'is_available' => true,
            'description' => 'Beach cottage',
        ]);

        $externalBooking = (string) Str::uuid();
        $payload = [
            'external_id' => $externalBooking,
            'client_external_id' => $externalUser,
            'mysql_unit_id' => $unit->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_count' => 2,
            'payment_method' => 'gcash',
        ];

        $created = $this->withTokenHeader()->postJson('/api/integration/bookings', $payload);
        $created->assertCreated();
        $created->assertJsonPath('status', 'pending');
        $this->assertNotEmpty($created->json('reference_no'));

        $again = $this->withTokenHeader()->postJson('/api/integration/bookings', $payload);
        $again->assertOk();
        $again->assertJsonPath('reference_no', $created->json('reference_no'));
        $this->assertSame(1, Booking::count());

        $conflict = $this->withTokenHeader()->postJson('/api/integration/bookings', [
            ...$payload,
            'external_id' => (string) Str::uuid(),
        ]);
        $conflict->assertStatus(409);
    }

    public function test_admin_status_change_does_not_require_supabase_to_succeed(): void
    {
        $user = User::factory()->create(['role' => 'tourist', 'external_id' => (string) Str::uuid()]);
        $unit = AccommodationUnit::create([
            'unit_number' => 'Room 02',
            'unit_type' => 'room',
            'variant' => 'normal',
            'max_occupancy' => 2,
            'price_per_night' => 1500,
            'is_available' => true,
        ]);

        $booking = $this->withTokenHeader()->postJson('/api/integration/bookings', [
            'external_id' => (string) Str::uuid(),
            'client_external_id' => $user->external_id,
            'mysql_unit_id' => $unit->id,
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(21)->toDateString(),
            'guests_count' => 2,
            'payment_method' => 'cash',
        ])->assertCreated();

        $model = Booking::where('reference_no', $booking->json('reference_no'))->first();
        app(\App\Services\BookingService::class)->changeStatus($model, Booking::STATUS_PAID);
        $this->assertSame(Booking::STATUS_PAID, $model->fresh()->status);
    }

    private function withTokenHeader(): static
    {
        return $this->withHeader('X-Integration-Token', 'test-integration-token');
    }
}
