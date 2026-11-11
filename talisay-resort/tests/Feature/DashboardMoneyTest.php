<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingTransitionException;
use App\Models\AccommodationUnit;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\AutomationService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMoneyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AccommodationUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-10 10:00'));
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->unit = AccommodationUnit::create([
            'unit_number' => 'Room 01', 'unit_type' => 'room', 'variant' => 'normal',
            'max_occupancy' => 4, 'price_per_night' => 1500, 'is_available' => true,
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
            'reference_no' => 'TBR-MONEY' . $n,
            'booking_type' => 'regular',
            'booking_source' => 'online',
            'guest_name' => 'Ana Cruz',
            'accommodation_unit_id' => $this->unit->id,
            'booking_date' => '2026-12-10',
            'check_in_date' => '2026-12-10',
            'check_out_date' => '2026-12-12',
            'nights_count' => 2,
            'guests_count' => 2,
            'status' => 'paid',
            'total_amount' => 3000,
        ], $booking));
        Payment::create(array_merge([
            'booking_id' => $row->id,
            'amount' => 1500,
            'gateway' => 'gcash',
            'payment_channel' => 'gcash',
            'transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(5))),
            'status' => 'success',
            'is_cash_on_arrival' => false,
            'metadata' => ['payment_plan' => 'down', 'stay_total' => 3000, 'balance' => 1500],
        ], $payment));

        return $row->fresh('payment');
    }

    public function test_confirming_a_payment_records_when_it_was_paid(): void
    {
        $booking = $this->booking(['status' => 'pending'], ['status' => 'pending', 'proof_path' => 'proofs/a.jpg']);
        $this->assertNull($booking->payment->paid_at);

        Carbon::setTestNow(Carbon::parse('2026-12-11 15:30'));
        app(BookingService::class)->approvePayment($booking->payment);

        $this->assertSame('2026-12-11 15:30', $booking->payment->fresh()->paid_at->format('Y-m-d H:i'));
    }

    public function test_revenue_counts_money_on_the_day_it_was_confirmed(): void
    {
        $this->booking([], ['created_at' => '2026-11-28 09:00', 'paid_at' => '2026-12-02 09:00']);

        $this->assertSame(1500.0, Payment::revenueBetween(Carbon::parse('2026-12-01'), now()));
        $this->assertSame(0.0, Payment::revenueBetween(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30 23:59:59')));
    }

    public function test_balance_due_and_collecting_it(): void
    {
        $booking = $this->booking();
        $this->assertSame(1500.0, $booking->balanceDue());

        $this->actingAs($this->admin)
            ->post(route('payments.collect-balance', $booking->payment), ['balance_amount' => 1500, 'balance_method' => 'cash', 'balance_reference' => 'OR-1001'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $payment = $booking->payment->fresh();
        $this->assertSame(3000.0, (float) $payment->amount);
        $this->assertSame(0.0, $booking->fresh('payment')->balanceDue());
        $this->assertCount(1, $payment->balancePayments());
        $this->assertSame(3000.0, Payment::revenueBetween(now()->startOfDay(), now()));
        $this->assertTrue(AuditLog::where('action', 'payment_balance_collected')->exists());
    }

    public function test_cannot_collect_more_than_the_balance(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('payments.collect-balance', $booking->payment), ['balance_amount' => 2000, 'balance_method' => 'cash'])
            ->assertSessionHasErrors('balance_amount');

        $this->assertSame(1500.0, (float) $booking->payment->fresh()->amount);
    }

    public function test_guest_cannot_be_checked_in_before_the_arrival_date(): void
    {
        $booking = $this->booking(['booking_date' => '2026-12-15', 'check_in_date' => '2026-12-15', 'check_out_date' => '2026-12-16', 'nights_count' => 1]);

        $this->expectException(InvalidBookingTransitionException::class);
        app(BookingService::class)->changeStatus($booking, Booking::STATUS_CHECKED_IN);
    }

    public function test_paid_stay_that_ended_without_check_in_is_a_no_show(): void
    {
        $booking = $this->booking(['booking_date' => '2026-12-05', 'check_in_date' => '2026-12-05', 'check_out_date' => '2026-12-07']);

        $this->assertSame('no_show', $booking->listStage());
        $this->assertSame([$booking->id], Booking::query()->listStage('no_show')->pluck('id')->all());
        $this->assertSame([], Booking::query()->listStage('check_out')->pluck('id')->all());

        app(AutomationService::class)->handleNoShows();
        app(AutomationService::class)->handleNoShows();
        $this->assertSame(1, AuditLog::where('action', 'booking_ended_no_checkin')->count());
    }

    public function test_dashboard_shows_today_and_money_cards(): void
    {
        $this->booking();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Arrivals')
            ->assertSee('In-house now')
            ->assertSee('Balance due')
            ->assertSee('Partially paid')
            ->assertSee('₱1,500.00', false);
    }
}
