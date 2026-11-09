<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\Review;
use App\Models\UnitVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function unit(array $overrides = []): AccommodationUnit
    {
        return AccommodationUnit::create(array_merge([
            'unit_number' => 'ROOM DASH',
            'unit_type' => 'room',
            'variant' => 'normal',
            'max_occupancy' => 4,
            'price_per_night' => 1800,
            'is_available' => true,
            'amenities' => [],
            'description' => 'Dashboard test unit',
        ], $overrides));
    }

    public function test_dashboard_counts_live_stays_payments_variants_and_reviews(): void
    {
        UnitVariant::create([
            'slug' => 'family',
            'label' => 'Family',
            'price_per_night' => 2200,
            'applies_to' => 'room',
            'is_deluxe' => false,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Louie Calacat']);
        $room = $this->unit();
        $cottage = $this->unit([
            'unit_number' => 'COT DASH',
            'unit_type' => 'cottage',
            'variant' => 'premium',
        ]);
        $family = $this->unit([
            'unit_number' => 'ROOM FAM',
            'variant' => 'family',
        ]);

        $nightly = Booking::create([
            'reference_no' => 'TBR-NIGHT01',
            'user_id' => $guest->id,
            'accommodation_unit_id' => $room->id,
            'booking_date' => now()->toDateString(),
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'nights_count' => 1,
            'guests_count' => 2,
            'status' => 'paid',
            'stay_type' => 'night',
            'total_amount' => 1800,
        ]);
        Payment::create([
            'booking_id' => $nightly->id,
            'amount' => 1800,
            'gateway' => 'gcash',
            'status' => 'success',
            'transaction_id' => '1111222233334',
        ]);

        $hourly = Booking::create([
            'reference_no' => 'TBR-HOUR01',
            'user_id' => $guest->id,
            'accommodation_unit_id' => $family->id,
            'booking_date' => now()->toDateString(),
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->toDateString(),
            'nights_count' => 0,
            'hours_count' => 4,
            'hour_start' => now()->format('H:i:s'),
            'guests_count' => 1,
            'status' => 'paid',
            'stay_type' => 'hour',
            'total_amount' => 500,
        ]);
        Payment::create([
            'booking_id' => $hourly->id,
            'amount' => 500,
            'gateway' => 'cash',
            'status' => 'success',
            'transaction_id' => '5555666677778',
        ]);

        $cancelled = Booking::create([
            'reference_no' => 'TBR-CANCEL1',
            'user_id' => $guest->id,
            'accommodation_unit_id' => $cottage->id,
            'booking_date' => now()->toDateString(),
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'nights_count' => 1,
            'guests_count' => 4,
            'status' => 'cancelled',
            'total_amount' => 900,
        ]);
        Payment::create([
            'booking_id' => $cancelled->id,
            'amount' => 900,
            'gateway' => 'gcash',
            'status' => 'refunded',
            'metadata' => ['paid_amount' => 900, 'refund_amount' => 900],
        ]);
        Payment::create([
            'booking_id' => $cancelled->id,
            'amount' => 400,
            'gateway' => 'cash',
            'status' => 'refunded',
            'metadata' => ['refund_sent' => ['amount' => 400]],
        ]);

        Review::create([
            'user_id' => $guest->id,
            'booking_id' => $nightly->id,
            'rating' => 5,
            'comment' => 'Quiet room and clear water.',
            'is_approved' => true,
        ]);

        SystemSetting::set('daily_visitor_cap', '30');

        $response = $this->actingAs($this->admin())->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-dash="arrivals">2', false);
        $response->assertDontSee('data-dash="in-house"', false);
        $response->assertSee('data-dash="capacity">3 / 30', false);
        $response->assertSee('Daily visitor cap');
        $response->assertSee('data-dash="refund-due">₱900.00', false);
        $response->assertSee('data-dash="refunded">₱400.00', false);
        $response->assertSee('Need to refund');
        $response->assertDontSee('In house');
        $response->assertSee('data-dash="revenue">₱2,300.00', false);
        $response->assertSee('TBR-NIGHT01');
        $response->assertSee('TBR-HOUR01');
        $response->assertSee('TBR-CANCEL1');
        $response->assertSee('ROOM DASH');
        $response->assertSee('ROOM FAM');
        $response->assertSee('Family');
        $response->assertSee('Hourly');
        $response->assertSee('Nightly');
        $response->assertSee('₱1,800.00');
        $response->assertSee('GCash');
        $response->assertSee('ROOM DASH · Normal', false);
        $response->assertSee('id="occupancyChart"', false);
        $response->assertSee('id="sentimentChart"', false);
        $response->assertDontSee('Quick operations');
        $response->assertDontSee('Next arrivals');
        $response->assertDontSee('Rooms · Normal', false);
        $response->assertDontSee('0/100', false);
        $response->assertDontSee('No paid stays yet.');
        $response->assertDontSee('COT DASH · Deluxe', false);
    }

    public function test_empty_dashboard_explains_the_charts_instead_of_a_blank_legend(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-dash="capacity">0 / 100', false);
        $response->assertSee('No confirmed payments in the last 6 months.');
        $response->assertSee('No rooms or cottages are in use this week.');
        $response->assertSee('No paid stays yet.');
        $response->assertSee('No approved reviews yet.');
        $response->assertSee('No payments yet.');
        $response->assertDontSee('Quick operations');
        $response->assertDontSee('Next arrivals');
        $response->assertDontSee('id="occupancyChart"', false);
        $response->assertDontSee('id="sentimentChart"', false);
        $response->assertDontSee('0/100', false);
    }

    public function test_approved_exclusive_booking_fills_every_unit(): void
    {
        SystemSetting::set('daily_visitor_cap', '40');
        $this->unit(['unit_number' => 'ROOM A']);
        $this->unit(['unit_number' => 'ROOM B']);
        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Exclusive Guest']);

        Booking::create([
            'reference_no' => 'TBR-EXCL01',
            'booking_type' => 'special_resort',
            'admin_approval_status' => 'approved',
            'user_id' => $guest->id,
            'booking_date' => now()->toDateString(),
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'nights_count' => 1,
            'guests_count' => 12,
            'status' => 'paid',
            'total_amount' => 20000,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-dash="capacity">40 / 40', false);
        $response->assertSee('Full resort');
        $response->assertSee('TBR-EXCL01');
    }
}
