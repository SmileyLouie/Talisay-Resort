<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TouristPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnit(array $overrides = []): AccommodationUnit
    {
        return AccommodationUnit::create(array_merge([
            'unit_number'     => 'Room 01',
            'unit_type'       => 'room',
            'variant'         => 'normal',
            'max_occupancy'   => 4,
            'price_per_night' => 500.00,
            'is_available'    => true,
            'amenities'       => ['Air Conditioning', 'Free Wi-Fi'],
            'description'     => 'Test unit',
        ], $overrides));
    }

    private function makeBooking(User $tourist, AccommodationUnit $unit, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'reference_no'          => 'TBR-TEST' . rand(1000, 9999),
            'user_id'               => $tourist->id,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(3)->format('Y-m-d'),
            'check_in_date'         => now()->addDays(3)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(4)->format('Y-m-d'),
            'nights_count'          => 1,
            'guests_count'          => 2,
            'status'                => 'pending',
            'total_amount'          => 500.00,
        ], $overrides));
    }

    public function test_tourist_can_login_and_access_tourist_dashboard(): void
    {
        $tourist = User::factory()->create([
            'role'     => 'tourist',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $tourist->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('tourist.dashboard'));

        $this->actingAs($tourist);
        $dashboardResponse = $this->get('/tourist/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    public function test_tourist_can_place_online_booking(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 01', 'price_per_night' => 500.00]);

        $this->actingAs($tourist);

        $response = $this->post('/tourist/bookings', [
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(2)->format('Y-m-d'),
            'check_in_date'         => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(3)->format('Y-m-d'),
            'nights_count'          => 1,
            'guests_count'          => 2,
            'special_requests'      => 'Cottage near water',
            'payment_method'        => 'gcash',
        ]);

        $response->assertRedirect(route('tourist.bookings'));

        $this->assertDatabaseHas('bookings', [
            'user_id'               => $tourist->id,
            'accommodation_unit_id' => $unit->id,
            'guests_count'          => 2,
            'status'                => 'pending',
        ]);
        $this->assertDatabaseHas('payments', [
            'payment_channel' => 'gcash',
            'amount'          => 500.00,
        ]);
    }

    public function test_tourist_can_book_with_cash_on_arrival(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 02', 'price_per_night' => 800.00]);

        $this->actingAs($tourist);

        $response = $this->post('/tourist/bookings', [
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(3)->format('Y-m-d'),
            'check_in_date'         => now()->addDays(3)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(4)->format('Y-m-d'),
            'nights_count'          => 1,
            'guests_count'          => 3,
            'payment_method'        => 'cash',
        ]);

        $response->assertRedirect(route('tourist.bookings'));

        $this->assertDatabaseHas('bookings', [
            'user_id'      => $tourist->id,
            'guests_count' => 3,
            'status'       => 'pending',
        ]);
        $this->assertDatabaseHas('payments', [
            'payment_channel'    => 'cash',
            'is_cash_on_arrival' => true,
            'amount'             => 800.00,
        ]);
    }

    public function test_tourist_can_modify_and_upgrade_booking(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit1   = $this->makeUnit(['unit_number' => 'Room 01', 'price_per_night' => 500.00]);
        $unit2   = $this->makeUnit(['unit_number' => 'Cottage 06', 'unit_type' => 'cottage', 'variant' => 'premium', 'price_per_night' => 1500.00]);

        $booking = $this->makeBooking($tourist, $unit1, [
            'reference_no' => 'TBR-MOD001',
            'total_amount' => 500.00,
        ]);

        $this->actingAs($tourist);

        $response = $this->post("/tourist/bookings/{$booking->id}/modify", [
            'new_accommodation_unit_id' => $unit2->id,
            'modification_notes'        => 'Upgraded to cottage for family comfort',
        ]);

        $response->assertRedirect(route('tourist.bookings'));

        $this->assertDatabaseHas('bookings', [
            'id'                         => $booking->id,
            'accommodation_unit_id'      => $unit2->id,
            'modification_notes'         => 'Upgraded to cottage for family comfort',
        ]);
    }

    public function test_tourist_can_request_special_full_resort_booking(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        // Controller needs at least one available unit to calculate pricing
        $this->makeUnit(['unit_number' => 'Room 01', 'price_per_night' => 1500.00]);

        $this->actingAs($tourist);

        $response = $this->post('/tourist/bookings/special-resort', [
            'check_in_date'    => now()->addDays(10)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(12)->format('Y-m-d'),
            'guests_count'     => 100,
            'special_requests' => 'Private wedding reception and catering setup',
            'payment_method'   => 'gcash',
        ]);

        $response->assertRedirect(route('tourist.bookings'));

        $this->assertDatabaseHas('bookings', [
            'user_id'               => $tourist->id,
            'booking_type'          => 'special_resort',
            'admin_approval_status' => 'pending',
            'nights_count'          => 2,
        ]);
    }

    public function test_tourist_can_browse_and_view_accommodation_units(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit = $this->makeUnit([
            'unit_number'     => 'Room 01',
            'variant'         => 'premium',
            'max_occupancy'   => 2,
            'price_per_night' => 2500.00,
            'amenities'       => ['Air-conditioning', 'Bathtub', 'Smart TV'],
            'description'     => 'Luxury room with 360 tour',
        ]);

        $this->actingAs($tourist);

        $indexRes = $this->get('/tourist/accommodations');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Room 01');

        $detailRes = $this->get("/tourist/accommodations/{$unit->id}");
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Room 01');
        $detailRes->assertSee('Air-conditioning');
    }

    public function test_tourist_can_submit_review(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Room 03']);
        $booking = $this->makeBooking($tourist, $unit, [
            'reference_no' => 'TBR-TEST123',
            'status'       => 'completed',
            'booking_date' => now()->subDays(2)->format('Y-m-d'),
            'check_in_date'  => now()->subDays(2)->format('Y-m-d'),
            'check_out_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $this->actingAs($tourist);

        $response = $this->post('/tourist/reviews', [
            'booking_id' => $booking->id,
            'rating'     => 5,
            'comment'    => 'Amazing resort stay! Friendly staff and clean beach.',
        ]);

        $response->assertRedirect(route('tourist.reviews'));

        $this->assertDatabaseHas('reviews', [
            'user_id'    => $tourist->id,
            'booking_id' => $booking->id,
            'rating'     => 5,
            'comment'    => 'Amazing resort stay! Friendly staff and clean beach.',
        ]);
    }

    public function test_tourist_cannot_submit_duplicate_review(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Room 04']);
        $booking = $this->makeBooking($tourist, $unit, [
            'reference_no' => 'TBR-DUP001',
            'status'       => 'completed',
            'total_amount' => 500.00,
            'booking_date' => now()->subDays(2)->format('Y-m-d'),
            'check_in_date'  => now()->subDays(2)->format('Y-m-d'),
            'check_out_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $this->actingAs($tourist);

        // Submit first review
        $this->post('/tourist/reviews', [
            'booking_id' => $booking->id,
            'rating'     => 5,
            'comment'    => 'First review',
        ]);

        // Submit duplicate review
        $response = $this->post('/tourist/reviews', [
            'booking_id' => $booking->id,
            'rating'     => 4,
            'comment'    => 'Duplicate review attempt',
        ]);

        $response->assertSessionHas('error', 'You have already submitted a review for this booking.');
        $this->assertEquals(1, \App\Models\Review::where('booking_id', $booking->id)->count());
    }

    public function test_tourist_cannot_modify_booking_to_same_unit(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Room 05']);

        $booking = $this->makeBooking($tourist, $unit, [
            'reference_no' => 'TBR-SAME01',
        ]);

        $this->actingAs($tourist);

        $response = $this->post("/tourist/bookings/{$booking->id}/modify", [
            'new_accommodation_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('new_accommodation_unit_id');
    }
}
