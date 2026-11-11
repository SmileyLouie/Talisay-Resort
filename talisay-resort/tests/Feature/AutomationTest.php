<?php

namespace Tests\Feature;

use App\Exceptions\BookingConflictException;
use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\NotificationModel;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AutomationService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $guest;

    private User $admin;

    private AccommodationUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00'));
        $this->guest = User::factory()->create(['role' => 'tourist']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->unit = AccommodationUnit::create([
            'unit_number' => 'Room 01', 'unit_type' => 'room', 'variant' => 'normal',
            'max_occupancy' => 4, 'price_per_night' => 1000, 'is_available' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function booking(array $booking = [], array $payment = []): Booking
    {
        static $n = 0;
        $n++;
        $row = Booking::create(array_merge([
            'reference_no' => 'TBR-AUTO' . $n,
            'booking_type' => 'regular',
            'booking_source' => 'online',
            'user_id' => $this->guest->id,
            'accommodation_unit_id' => $this->unit->id,
            'booking_date' => '2026-12-05',
            'check_in_date' => '2026-12-05',
            'check_out_date' => '2026-12-06',
            'nights_count' => 1,
            'guests_count' => 2,
            'status' => 'pending',
            'total_amount' => 1000,
        ], $booking));
        Payment::create(array_merge([
            'booking_id' => $row->id,
            'amount' => 1000,
            'gateway' => 'gcash',
            'payment_channel' => 'gcash',
            'transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(5))),
            'status' => 'pending',
            'is_cash_on_arrival' => false,
        ], $payment));

        return $row;
    }

    private function automation(): AutomationService
    {
        return app(AutomationService::class);
    }

    public function test_unpaid_online_booking_is_released_after_the_hold_time(): void
    {
        Carbon::setTestNow('2026-12-01 06:00');
        $noReceipt = $this->booking();
        $withReceipt = $this->booking([], ['proof_path' => 'payments/r.png', 'transaction_id' => '1234567890123']);
        $cash = $this->booking([], ['gateway' => 'cash', 'payment_channel' => 'cash', 'is_cash_on_arrival' => true]);
        $card = $this->booking([], ['gateway' => 'card', 'payment_channel' => 'card']);
        $desk = $this->booking(['booking_source' => 'manual']);

        Carbon::setTestNow('2026-12-01 07:30');
        $fresh = $this->booking();

        Carbon::setTestNow('2026-12-01 09:00');
        $this->assertSame(1, $this->automation()->releaseUnpaidHolds());

        $this->assertSame('cancelled', $noReceipt->fresh()->status);
        foreach ([$withReceipt, $cash, $card, $desk, $fresh] as $kept) {
            $this->assertSame('pending', $kept->fresh()->status, $kept->reference_no);
        }
        $this->assertDatabaseHas('notifications_table', ['user_id' => $this->guest->id, 'title' => 'Reservation Released']);
    }

    public function test_hold_release_can_be_turned_off(): void
    {
        SystemSetting::set('hold_minutes', '0');
        Carbon::setTestNow('2026-11-30 09:00');
        $booking = $this->booking();
        Carbon::setTestNow('2026-12-01 09:00');

        $this->assertSame(0, $this->automation()->releaseUnpaidHolds());
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_unpaid_no_show_is_cancelled_but_paid_guest_only_raises_an_alert(): void
    {
        $unpaid = $this->booking(['check_in_date' => '2026-12-01', 'booking_date' => '2026-12-01', 'check_out_date' => '2026-12-02'], ['gateway' => 'cash', 'payment_channel' => 'cash', 'is_cash_on_arrival' => true]);
        $paid = $this->booking(['check_in_date' => '2026-12-01', 'booking_date' => '2026-12-01', 'check_out_date' => '2026-12-02', 'status' => 'paid'], ['status' => 'success']);

        Carbon::setTestNow('2026-12-01 14:30');
        $this->assertSame(0, $this->automation()->handleNoShows());
        $this->assertSame('pending', $unpaid->fresh()->status);

        Carbon::setTestNow('2026-12-01 15:05');
        $this->assertSame(1, $this->automation()->handleNoShows());
        $this->assertSame('cancelled', $unpaid->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame(1, NotificationModel::where('user_id', $this->admin->id)->where('title', 'Guest Has Not Checked In')->count());

        $this->automation()->handleNoShows();
        $this->assertSame(1, NotificationModel::where('user_id', $this->admin->id)->where('title', 'Guest Has Not Checked In')->count());
    }

    public function test_stay_is_checked_out_then_completed_automatically(): void
    {
        $booking = $this->booking(['check_in_date' => '2026-11-30', 'booking_date' => '2026-11-30', 'check_out_date' => '2026-12-01', 'status' => 'checked_in'], ['status' => 'success']);

        Carbon::setTestNow('2026-12-01 12:30');
        $this->assertSame(0, $this->automation()->autoCheckOut());

        Carbon::setTestNow('2026-12-01 13:05');
        $this->assertSame(1, $this->automation()->autoCheckOut());
        $this->assertSame('checked_out', $booking->fresh()->status);
        $this->assertSame(0, $this->automation()->autoComplete());

        Carbon::setTestNow('2026-12-02 12:05');
        $this->assertSame(1, $this->automation()->autoComplete());
        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_hourly_stay_is_checked_out_on_the_same_day(): void
    {
        $booking = $this->booking([
            'check_in_date' => '2026-12-01', 'booking_date' => '2026-12-01', 'check_out_date' => '2026-12-02',
            'nights_count' => 0, 'stay_type' => 'hour', 'hours_count' => 3, 'hour_start' => '08:00', 'status' => 'checked_in',
        ], ['status' => 'success']);

        Carbon::setTestNow('2026-12-01 12:05');
        $this->assertSame(1, $this->automation()->autoCheckOut());
        $this->assertSame('checked_out', $booking->fresh()->status);
    }

    public function test_arrival_reminder_is_sent_once_with_access_details_after_payment(): void
    {
        SystemSetting::set('precheckin_links', '1');
        SystemSetting::set('precheckin_days', '2');
        SystemSetting::set('access_instructions', 'Gate code 4321');
        $soon = $this->booking(['check_in_date' => '2026-12-03', 'booking_date' => '2026-12-03', 'check_out_date' => '2026-12-04', 'status' => 'paid'], ['status' => 'success']);
        $this->booking(['check_in_date' => '2026-12-10', 'booking_date' => '2026-12-10', 'check_out_date' => '2026-12-11']);

        $this->assertSame(1, $this->automation()->sendArrivalReminders());
        $this->assertSame(0, $this->automation()->sendArrivalReminders());

        $note = NotificationModel::where('user_id', $this->guest->id)->where('title', 'Your Stay Is Coming Up')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('Gate code 4321', $note->message);
        $this->assertStringContainsString($soon->reference_no, $note->message);
    }

    public function test_reminders_stay_off_until_enabled(): void
    {
        $this->booking(['check_in_date' => '2026-12-02', 'booking_date' => '2026-12-02', 'check_out_date' => '2026-12-03']);

        $this->assertSame(0, $this->automation()->sendArrivalReminders());
    }

    public function test_old_notifications_are_removed_after_the_retention_period(): void
    {
        SystemSetting::set('data_retention_months', '6');
        $old = NotificationModel::notifyUser($this->guest->id, 'system', 'Old', 'Old message');
        $old->forceFill(['created_at' => now()->subMonths(7)])->save();
        $recent = NotificationModel::notifyUser($this->guest->id, 'system', 'Recent', 'Recent message');

        $this->assertSame(1, $this->automation()->pruneOldData()['old_notifications']);
        $this->assertNull(NotificationModel::find($old->id));
        $this->assertNotNull(NotificationModel::find($recent->id));
    }

    public function test_down_payment_follows_the_setting(): void
    {
        SystemSetting::set('down_payment_percent', '30');
        $booking = app(BookingService::class)->createRegular([
            'unit' => $this->unit, 'check_in' => '2026-12-05', 'check_out' => '2026-12-06', 'guests_count' => 2,
            'user_id' => $this->guest->id, 'payment_method' => 'gcash', 'payment_plan' => 'down',
        ]);
        $this->assertEquals(300, (float) $booking->payment->amount);
        $this->assertSame(30, $booking->payment->metadata['down_payment_percent']);

        SystemSetting::set('down_payment_percent', '0');
        $full = app(BookingService::class)->createRegular([
            'unit' => $this->unit, 'check_in' => '2026-12-08', 'check_out' => '2026-12-09', 'guests_count' => 2,
            'user_id' => $this->guest->id, 'payment_method' => 'gcash', 'payment_plan' => 'down',
        ]);
        $this->assertEquals(1000, (float) $full->payment->amount);
        $this->assertSame('full', $full->payment->metadata['payment_plan']);
    }

    public function test_minimum_nights_applies_to_online_guests_only(): void
    {
        SystemSetting::set('min_nights', '2');
        $service = app(BookingService::class);
        $stay = ['unit' => $this->unit, 'check_in' => '2026-12-05', 'check_out' => '2026-12-06', 'guests_count' => 2, 'payment_method' => 'cash'];

        try {
            $service->createRegular($stay + ['user_id' => $this->guest->id]);
            $this->fail('A one-night online booking should be refused.');
        } catch (BookingConflictException $e) {
            $this->assertStringContainsString('at least 2 nights', $e->getMessage());
        }

        $desk = $service->createRegular($stay + ['booking_source' => 'manual', 'guest_name_manual' => 'Walk-in']);
        $this->assertSame(1, (int) $desk->nights_count);
    }

    public function test_hourly_stays_can_be_switched_off_for_guests(): void
    {
        SystemSetting::set('hourly_stays_enabled', '0');

        $this->expectException(BookingConflictException::class);
        app(BookingService::class)->createRegular([
            'unit' => $this->unit, 'check_in' => '2026-12-05', 'stay_type' => 'hour', 'hours_count' => 2, 'hour_start' => '10:00',
            'guests_count' => 2, 'user_id' => $this->guest->id, 'payment_method' => 'cash',
        ]);
    }

    public function test_time_slot_uses_the_check_in_and_check_out_settings(): void
    {
        SystemSetting::set('check_in_time', '15:00');
        SystemSetting::set('check_out_time', '11:00');
        $booking = app(BookingService::class)->createRegular([
            'unit' => $this->unit, 'check_in' => '2026-12-05', 'check_out' => '2026-12-06', 'guests_count' => 2,
            'user_id' => $this->guest->id, 'payment_method' => 'cash',
        ]);

        $this->assertSame('3:00 PM Check-in · 11:00 AM Check-out', $booking->time_slot);
        $this->assertSame('2026-12-05 15:00', $booking->arrivalAt()->format('Y-m-d H:i'));
        $this->assertSame('2026-12-06 11:00', $booking->departureAt()->format('Y-m-d H:i'));
    }

    public function test_staff_alert_for_new_booking_follows_the_setting(): void
    {
        SystemSetting::set('notify_new_booking', '0');
        $this->actingAs($this->guest)->post('/tourist/bookings', [
            'accommodation_unit_id' => $this->unit->id,
            'booking_date' => '2026-12-05',
            'check_out_date' => '2026-12-06',
            'guests_count' => 2,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('bookings', ['user_id' => $this->guest->id]);
        $this->assertDatabaseMissing('notifications_table', ['user_id' => $this->admin->id, 'title' => 'New Reservation Received']);
    }

    public function test_automate_command_runs_every_group(): void
    {
        $this->artisan('resort:automate')->assertSuccessful();
        $this->artisan('resort:automate', ['group' => 'nope'])->assertFailed();
    }
}
