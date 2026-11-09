<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\PaymentMethod;
use App\Models\ResortActivity;
use App\Models\SystemSetting;
use App\Models\UnitVariant;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileMaintenanceConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_maintenance_lists_connect_to_bookings(): void
    {
        $guest = User::factory()->create(['role' => 'tourist', 'is_active' => true, 'account_status' => 'active']);
        $unit = AccommodationUnit::create([
            'unit_number' => 'Room 9',
            'unit_type' => 'room',
            'variant' => 'normal',
            'max_occupancy' => 4,
            'price_per_night' => 1800,
            'is_available' => true,
        ]);
        $variant = UnitVariant::create([
            'slug' => 'family',
            'label' => 'Family',
            'applies_to' => 'room',
            'price_per_night' => 2200,
            'is_active' => true,
        ]);
        $activity = ResortActivity::create([
            'title' => 'Kayaking',
            'description' => 'Morning paddle',
            'schedule' => 'Daily, 8:00 AM',
            'price' => 400,
            'is_active' => true,
            'is_bookable' => true,
        ]);
        PaymentMethod::create([
            'code' => 'maya',
            'name' => 'Maya',
            'kind' => 'ewallet',
            'account_name' => 'Talisay Beach Resort',
            'account_number' => '09171234567',
            'is_active' => true,
        ]);
        PaymentMethod::create([
            'code' => 'bdo',
            'name' => 'BDO',
            'kind' => 'bank',
            'account_name' => 'Talisay Beach Resort',
            'account_number' => '001234567890',
            'is_active' => true,
        ]);
        PaymentMethod::create([
            'code' => 'landbank',
            'name' => 'Landbank',
            'kind' => 'bank',
            'is_active' => true,
        ]);
        SystemSetting::set('exclusive_nightly_rate', '9000');
        SystemSetting::set('exclusive_occasions', "Wedding or reception\nDebut");

        $this->assertTrue(UnitVariant::forKind('room')->pluck('slug')->contains('family'));
        $this->assertFalse(UnitVariant::forKind('cottage')->pluck('slug')->contains('family'));
        $this->assertEquals(2200, (float) $variant->price_per_night);
        $this->assertTrue(PaymentMethod::catalog()->pluck('code')->contains('maya'));
        $this->assertTrue(PaymentMethod::catalog()->pluck('code')->contains('bdo'));
        $this->assertSame('bank', PaymentMethod::kindOf('bdo'));
        $this->assertTrue(PaymentMethod::needsProof('bdo'));
        $this->assertFalse(PaymentMethod::catalog()->pluck('code')->contains('landbank'));
        $this->assertSame(9000.0, app(BookingService::class)->exclusiveNightlyRate());
        $this->assertSame(['Wedding or reception', 'Debut'], Booking::occasions());

        $booking = app(BookingService::class)->createRegular([
            'unit' => $unit,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests_count' => 2,
            'user_id' => $guest->id,
            'payment_method' => 'maya',
            'activity_ids' => [$activity->id],
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->assertSame('2200.00', number_format((float) $booking->total_amount, 2, '.', ''));
        $this->assertSame('Kayaking', $booking->activities()->first()->title);
        $this->assertSame('maya', $booking->payment->gateway);
    }

    public function test_file_maintenance_screens_expose_the_connected_fields(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'account_status' => 'active']);
        ResortActivity::create([
            'title' => 'Kayaking',
            'price' => 400,
            'is_active' => true,
            'is_bookable' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'amenities'))
            ->assertOk()
            ->assertSee('Add amenity')
            ->assertDontSee('Icon class');

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'variants'))
            ->assertOk()
            ->assertSee('Nightly price')
            ->assertSee('Rooms only')
            ->assertSee('Cottages only')
            ->assertDontSee('Treat as Deluxe');

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'exclusive'))
            ->assertOk()
            ->assertSee('What is this booking for?')
            ->assertSee('Description')
            ->assertSee('Information')
            ->assertSee('Exclusive full resort');

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'payments'))
            ->assertOk()
            ->assertSee('Maya')
            ->assertSee('BDO')
            ->assertSee('E-wallets')
            ->assertSee('Banks')
            ->assertSee('Add all banks')
            ->assertDontSee('Edit account')
            ->assertSee('Card processor');

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'gateway'))
            ->assertRedirect(route('maintenance.section', 'payments'));

        $this->actingAs($admin)
            ->post(route('maintenance.payments.install'), ['codes' => ['bpi']])
            ->assertRedirect(route('maintenance.section', 'payments'));
        $this->assertDatabaseHas('payment_methods', ['code' => 'bpi', 'kind' => 'bank']);
        $this->assertFalse(PaymentMethod::catalog()->pluck('code')->contains('bpi'));

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'assistant'))
            ->assertOk()
            ->assertSee('Base URL')
            ->assertSee('Never send contact details')
            ->assertSee('Test connection');

        $this->actingAs($admin)
            ->get(route('accommodations.index'))
            ->assertOk()
            ->assertSee('applyVariantRate');

        $overview = $this->actingAs($admin)->get(route('maintenance.index'))->assertOk()->getContent();
        $navStart = strpos($overview, 'aria-label="File maintenance"');
        $navEnd = strpos($overview, '</nav>', $navStart);
        $nav = substr($overview, $navStart, $navEnd - $navStart);
        $this->assertStringNotContainsString('Property', $nav);
        $this->assertStringNotContainsString('Operations', $nav);
        $this->assertStringNotContainsString('Staff', $nav);
        $this->assertStringNotContainsString('Payment gateway', $nav);
        $this->assertStringContainsString('Payments', $nav);
        $this->assertStringContainsString('AI assistant', $nav);
        $this->assertStringNotContainsString('Activities', $nav);

        $this->actingAs($admin)
            ->get(route('maintenance.section', 'activities'))
            ->assertRedirect(route('activities.manage'));

        $this->actingAs($admin)
            ->get(route('activities.manage'))
            ->assertOk()
            ->assertSee('Kayaking')
            ->assertSee('What guests do')
            ->assertSee('Group size')
            ->assertDontSee('Meeting point')
            ->assertDontSee('What to bring');

        $this->actingAs($admin)
            ->post(route('activities.store'), [
                'title' => 'Island hopping',
                'description' => 'Visit nearby islands by boat.',
                'schedule' => 'Daily, 9:00 AM',
                'duration' => '4 hours',
                'price' => 1500,
                'meeting_point' => 'Resort jetty',
                'what_to_bring' => 'Towel and sunscreen',
                'included' => 'Boat and guide',
                'max_guests' => 8,
                'is_active' => 1,
                'is_bookable' => 1,
            ])
            ->assertRedirect(route('activities.manage'));
        $this->assertDatabaseHas('resort_activities', [
            'title' => 'Island hopping',
            'duration' => '4 hours',
            'meeting_point' => 'Resort jetty',
        ]);
    }

    public function test_new_variants_appear_on_the_room_and_cottage_cards(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'account_status' => 'active']);
        UnitVariant::create([
            'slug' => 'family',
            'label' => 'Family',
            'applies_to' => 'room',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        UnitVariant::create([
            'slug' => 'garden',
            'label' => 'Garden',
            'applies_to' => 'cottage',
            'is_active' => true,
            'sort_order' => 4,
        ]);
        AccommodationUnit::create([
            'unit_number' => 'ROOM 9',
            'unit_type' => 'room',
            'variant' => 'family',
            'max_occupancy' => 6,
            'price_per_night' => 2500,
            'price_per_hour' => 200,
            'is_available' => true,
        ]);

        $rooms = $this->actingAs($admin)->get(route('accommodations.index'));
        $rooms->assertOk();
        $rooms->assertSee('Family');
        $rooms->assertSee('unit-stats-variants', false);
        $rooms->assertDontSee('Garden');

        $cottages = $this->actingAs($admin)->get(route('accommodations.cottages'));
        $cottages->assertOk();
        $cottages->assertSee('Garden');
        $cottages->assertDontSee('Family');
    }

    public function test_exclusive_copy_and_visitor_cap_stay_connected(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'account_status' => 'active']);
        $row = CapacitySchedule::create([
            'date' => now()->toDateString(),
            'max_capacity' => 100,
            'current_count' => 4,
        ]);

        $this->actingAs($admin)->put(route('settings.save'), [
            'settings' => [
                'daily_visitor_cap' => '300',
                'exclusive_title' => 'Whole resort night',
                'exclusive_description' => 'A private night for one group only.',
                'exclusive_information' => 'Every room and cottage is included.',
            ],
        ])->assertRedirect();

        $this->assertSame(300, CapacitySchedule::visitorCap());
        $this->assertSame(300, (int) $row->fresh()->max_capacity);
        $this->assertSame('Whole resort night', Booking::exclusiveCopy()['title']);
        $this->assertSame('A private night for one group only.', Booking::exclusiveCopy()['description']);

        $this->get(route('public.booking.exclusive', [
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Whole resort night')
            ->assertSee('A private night for one group only.')
            ->assertSee('Every room and cottage is included.')
            ->assertSee('Up to 300 guests');
    }
}
