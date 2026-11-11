<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
    }

    private function makeStaff(array $permissions = []): User
    {
        $staff = User::factory()->create([
            'role'           => 'staff',
            'is_active'      => true,
            'account_status' => 'active',
        ]);

        $modules = empty($permissions) ? [
            'bookings'       => ['view', 'create', 'edit', 'confirm', 'cancel'],
            'payments'       => ['view', 'verify', 'refund'],
            'reviews'        => ['view', 'approve', 'reject', 'block_comment'],
            'accommodations' => ['view', 'create', 'edit', 'delete', 'toggle_availability'],
            'reports'        => ['view', 'export'],
        ] : $permissions;

        foreach ($modules as $module => $actions) {
            StaffPermission::create([
                'user_id' => $staff->id,
                'module'  => $module,
                'actions' => (array) $actions,
            ]);
        }

        return $staff;
    }

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

    private function makeBooking(User $guest, AccommodationUnit $unit, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'reference_no'          => 'TBR-TEST' . rand(1000, 9999),
            'user_id'               => $guest->id,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->format('Y-m-d'),
            'check_in_date'         => now()->format('Y-m-d'),
            'check_out_date'        => now()->addDays(1)->format('Y-m-d'),
            'nights_count'          => 1,
            'guests_count'          => 2,
            'status'                => 'pending',
            'total_amount'          => 500.00,
        ], $overrides));
    }

    public function test_staff_can_login_and_access_staff_dashboard(): void
    {
        $staff = User::factory()->create([
            'role'     => 'staff',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $staff->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('staff.dashboard'));

        $this->actingAs($staff);
        $dashboardResponse = $this->get('/staff/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    public function test_admin_can_login_and_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role'     => 'admin',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin);
        $dashboardResponse = $this->get('/admin/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    public function test_admin_cannot_login_on_client_portal(): void
    {
        $admin = User::factory()->create([
            'role'     => 'admin',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
            'portal'   => 'client',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_staff_can_login_on_admin_portal_and_client_cannot(): void
    {
        $staff = User::factory()->create([
            'role'     => 'staff',
            'password' => bcrypt('password'),
        ]);

        $responseStaff = $this->post('/login', [
            'email'    => $staff->email,
            'password' => 'password',
            'portal'   => 'admin',
        ]);

        $responseStaff->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticatedAs($staff);

        auth()->logout();

        $tourist = User::factory()->create([
            'role'     => 'tourist',
            'password' => bcrypt('password'),
        ]);

        $responseTourist = $this->post('/login', [
            'email'    => $tourist->email,
            'password' => 'password',
            'portal'   => 'admin',
        ]);

        $this->assertGuest();
        $responseTourist->assertSessionHasErrors('email');
    }

    public function test_admin_can_login_on_admin_portal(): void
    {
        $admin = User::factory()->create([
            'role'     => 'admin',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
            'portal'   => 'admin',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_tourist_can_login_on_client_portal_and_staff_cannot(): void
    {
        $staff = User::factory()->create([
            'role'     => 'staff',
            'password' => bcrypt('password'),
        ]);

        $responseStaff = $this->post('/login', [
            'email'    => $staff->email,
            'password' => 'password',
            'portal'   => 'client',
        ]);

        $this->assertGuest();
        $responseStaff->assertSessionHasErrors('email');

        $tourist = User::factory()->create([
            'role'     => 'tourist',
            'password' => bcrypt('password'),
        ]);

        $responseTourist = $this->post('/login', [
            'email'    => $tourist->email,
            'password' => 'password',
            'portal'   => 'client',
        ]);

        $responseTourist->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($tourist);
    }

    public function test_client_and_staff_sites_use_different_local_addresses(): void
    {
        $this->get('http://127.0.0.1:8001/')
            ->assertOk()
            ->assertSee('Talisay')
            ->assertSee('Smart Tourism')
            ->assertSee('Login')
            ->assertDontSee('btn-landing-register');

        $this->get('http://127.0.0.1:8001/login')
            ->assertOk()
            ->assertSee('Login')
            ->assertDontSee('Staff log in')
            ->assertDontSee('Admin & Staff Log In')
            ->assertDontSee('Email or Staff ID')
            ->assertDontSee('Booking as a guest?')
            ->assertDontSee('http://127.0.0.1:8001');

        $this->get('http://127.0.0.1:8000/login?portal=admin')
            ->assertRedirect('http://127.0.0.1:8001/login');

        $this->get('http://127.0.0.1:8000/login')
            ->assertOk()
            ->assertSee('Client Log In')
            ->assertSee('http://127.0.0.1:8000');

        $this->get('http://127.0.0.1:8000/accommodations')
            ->assertRedirect('http://127.0.0.1:8001/accommodations');
    }

    public function test_staff_can_update_booking_status(): void
    {
        $staff   = $this->makeStaff();
        $guest   = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 01', 'price_per_night' => 800.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => 'TBR-TESTSTAFF1',
            'guests_count' => 4,
            'total_amount' => 800.00,
        ]);

        $this->actingAs($staff);

        $response = $this->patch("/bookings/{$booking->id}/status", [
            'status' => 'checked_in',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'checked_in',
        ]);
    }

    private function makePaidStay(int $daysAhead, string $paymentStatus = 'success', string $ref = 'TBR-REFUND'): array
    {
        $guest = User::factory()->create(['role' => 'tourist']);
        $unit = $this->makeUnit(['unit_number' => 'Room '.$ref, 'price_per_night' => 1500.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => $ref,
            'status' => $paymentStatus === 'success' ? 'paid' : 'pending',
            'booking_date' => now()->addDays($daysAhead)->format('Y-m-d'),
            'check_in_date' => now()->addDays($daysAhead)->format('Y-m-d'),
            'check_out_date' => now()->addDays($daysAhead + 1)->format('Y-m-d'),
            'total_amount' => 1500.00,
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id, 'amount' => 1500.00, 'gateway' => 'gcash',
            'status' => $paymentStatus, 'transaction_id' => '1234123123456', 'proof_path' => 'payment-proofs/r.jpg',
        ]);

        return [$guest, $booking, $payment];
    }

    public function test_declined_payment_the_guest_sent_can_be_refunded_once(): void
    {
        $this->actingAs($this->makeStaff());
        [$guest, $booking, $payment] = $this->makePaidStay(5, 'pending', 'TBR-DECLINED');

        $this->post("/payments/{$payment->id}/reject")
            ->assertRedirect()
            ->assertSessionHas('success', 'Payment declined. Booking TBR-DECLINED was cancelled and its room or cottage is free again.');
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('available', $payment->fresh()->refundState());
        $this->assertDatabaseHas('notifications_table', ['user_id' => $guest->id, 'title' => 'Payment declined, booking cancelled']);

        $this->actingAs($guest)
            ->post(route('tourist.payments.upload-proof', $payment), [
                'proof_image' => \Illuminate\Http\UploadedFile::fake()->image('again.jpg'),
                'reference_number' => '1234123123456',
            ])
            ->assertSessionHas('error', 'This booking was cancelled, so it no longer takes payments. Please make a new booking.');
        $this->assertSame('failed', $payment->fresh()->status);
        $this->actingAs($this->makeStaff());

        $this->post("/payments/{$payment->id}/refund", ['refund_amount' => 1500, 'refund_reference' => 'GC-REF-1', 'refund_note' => 'Room unavailable'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Refund of ₱1,500.00 recorded for TBR-DECLINED.');

        $payment->refresh();
        $this->assertSame('refunded', $payment->status);
        $this->assertSame('sent', $payment->refundState());
        $this->assertSame('GC-REF-1', $payment->refundSent()['reference']);
        $this->assertDatabaseHas('notifications_table', ['user_id' => $guest->id, 'title' => 'Refund sent']);

        $this->post("/payments/{$payment->id}/refund", ['refund_amount' => 100])
            ->assertSessionHasErrors(['refund_amount' => 'A refund was already recorded for this payment.']);
    }

    public function test_payment_stat_cards_match_the_payments_on_record(): void
    {
        $this->actingAs($this->makeStaff());
        $this->makePaidStay(10, 'success', 'TBR-STATPAID');
        $this->makePaidStay(11, 'pending', 'TBR-STATREVIEW');
        [, , $noReceipt] = $this->makePaidStay(12, 'pending', 'TBR-STATNORCPT');
        $noReceipt->update(['proof_path' => null, 'transaction_id' => null]);
        [, , $declined] = $this->makePaidStay(13, 'pending', 'TBR-STATREFUND');
        $this->post("/payments/{$declined->id}/reject");
        $this->post("/payments/{$declined->id}/refund", ['refund_amount' => 1500]);
        [, $deletedBooking] = $this->makePaidStay(14, 'success', 'TBR-STATGONE');
        $deletedBooking->delete();

        $response = $this->get(route('payments.index'))->assertOk();
        $this->assertSame(1500.0, $response->viewData('totalSuccess'));
        $this->assertSame(1500.0, $response->viewData('totalPending'));
        $this->assertSame(1, $response->viewData('pendingCount'));
        $this->assertSame(1, $response->viewData('awaitingReceiptCount'));
        $this->assertSame(1500.0, $response->viewData('refundSentTotal'));
        $this->assertSame(0, $response->viewData('refundDueCount'));
        $this->assertSame(4, $response->viewData('statusCounts')->sum());
        $response->assertSee('1 waiting for a receipt')
            ->assertSee('₱1,500.00 already refunded')
            ->assertDontSee('TBR-STATGONE');
    }

    public function test_declining_a_payment_does_not_cancel_a_booking_that_is_already_paid(): void
    {
        $this->actingAs($this->makeStaff());
        [, $booking, $payment] = $this->makePaidStay(5, 'success', 'TBR-KEEPPAID');

        $this->post("/payments/{$payment->id}/reject")->assertSessionHas('success', 'Payment declined.');
        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_cancelled_paid_booking_shows_refund_due_and_can_be_refunded(): void
    {
        $this->actingAs($this->makeStaff());
        [, $booking, $payment] = $this->makePaidStay(2, 'success', 'TBR-CANCELPART');

        app(\App\Services\BookingService::class)->cancel($booking, 'Guest cancelled');
        $payment->refresh();
        $this->assertSame('due', $payment->refundState());
        $this->assertSame(750.0, $payment->suggestedRefund());

        $this->get(route('payments.index', ['status' => 'refund_due']))
            ->assertOk()
            ->assertSee('TBR-CANCELPART')
            ->assertSee('Refund due ₱750.00');

        $this->post("/payments/{$payment->id}/refund", ['refund_amount' => 750])->assertSessionHasNoErrors();
        $payment->refresh();
        $this->assertSame('success', $payment->status);
        $this->assertSame('750.00', (string) $payment->amount);
        $this->assertSame(750.0, (float) $payment->metadata['refund_amount']);

        $this->patch(route('bookings.update-status', $booking), ['status' => 'paid'])
            ->assertSessionHas('error');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_refund_is_blocked_for_an_active_booking_and_without_permission(): void
    {
        [, , $payment] = $this->makePaidStay(5, 'success', 'TBR-ACTIVE');

        $this->actingAs($this->makeStaff(['payments' => ['view', 'verify']]));
        $this->post("/payments/{$payment->id}/refund", ['refund_amount' => 100])->assertSessionHas('error');
        $this->assertNull($payment->fresh()->refundSent());

        $this->actingAs($this->makeStaff());
        $this->post("/payments/{$payment->id}/refund", ['refund_amount' => 100])
            ->assertSessionHasErrors(['refund_amount' => 'Only a declined payment or a payment on a cancelled booking can be refunded. Cancel the booking first.']);
        $this->assertNull($payment->fresh()->refundSent());
    }

    public function test_staff_can_approve_payment(): void
    {
        $staff   = $this->makeStaff();
        $guest   = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Room 02', 'price_per_night' => 500.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => 'TBR-PAYTEST',
            'guests_count' => 1,
            'total_amount' => 500.00,
        ]);
        $payment = Payment::create([
            'booking_id'     => $booking->id,
            'amount'         => 500.00,
            'gateway'        => 'gcash',
            'status'         => 'pending',
            'transaction_id' => 'GCASH-123456',
        ]);

        $this->actingAs($staff);

        $response = $this->post("/payments/{$payment->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'paid',
        ]);
    }

    public function test_staff_can_approve_review(): void
    {
        $staff   = $this->makeStaff();
        $guest   = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 06', 'variant' => 'premium', 'price_per_night' => 2000.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => 'TBR-REVTEST',
            'guests_count' => 2,
            'status'       => 'completed',
            'total_amount' => 2000.00,
        ]);
        $review = Review::create([
            'user_id'    => $guest->id,
            'booking_id' => $booking->id,
            'rating'     => 5,
            'comment'    => 'Great experience!',
            'is_approved'=> false,
        ]);

        $this->actingAs($staff);

        $response = $this->put("/reviews/{$review->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'id'          => $review->id,
            'is_approved' => true,
        ]);
    }

    public function test_staff_can_block_and_unblock_review_comment_while_preserving_stars(): void
    {
        $staff   = $this->makeStaff();
        $guest   = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 07', 'variant' => 'premium', 'price_per_night' => 2000.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => 'TBR-REVTEST2',
            'guests_count' => 2,
            'status'       => 'completed',
            'total_amount' => 2000.00,
        ]);
        $review = Review::create([
            'user_id'            => $guest->id,
            'booking_id'         => $booking->id,
            'rating'             => 1, // 1 star review
            'comment'            => 'Terrible rude staff you suck!',
            'is_approved'        => true,
            'is_comment_blocked' => false,
        ]);

        $this->actingAs($staff);

        // Block comment
        $response = $this->put("/reviews/{$review->id}/block-comment", [
            'reason' => 'Inappropriate language / bad words',
        ]);
        $response->assertRedirect();

        // Verify comment is blocked, BUT star rating (1 star) remains permanently preserved!
        $this->assertDatabaseHas('reviews', [
            'id'                 => $review->id,
            'rating'             => 1, // Rating is strictly preserved!
            'is_comment_blocked' => true,
            'block_reason'       => 'Inappropriate language / bad words',
        ]);

        // Unblock comment
        $unblockRes = $this->put("/reviews/{$review->id}/unblock-comment");
        $unblockRes->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'id'                 => $review->id,
            'rating'             => 1,
            'is_comment_blocked' => false,
        ]);
    }

    public function test_reviews_page_shows_stats_and_filters_by_rating_and_search(): void
    {
        $staff = $this->makeStaff();
        $unit  = $this->makeUnit(['unit_number' => 'Cottage 09']);
        $make = function (string $name, string $ref, int $rating, string $comment, bool $blocked = false) use ($unit) {
            $guest = User::factory()->create(['role' => 'tourist', 'name' => $name]);
            $booking = $this->makeBooking($guest, $unit, ['reference_no' => $ref, 'status' => 'completed']);
            return Review::create([
                'user_id' => $guest->id, 'booking_id' => $booking->id, 'rating' => $rating,
                'comment' => $comment, 'is_approved' => true, 'is_comment_blocked' => $blocked,
                'block_reason' => $blocked ? 'Profanity or vulgar language' : null,
            ]);
        };
        $make('Ana Happy', 'TBR-REV-A', 5, 'Lovely beach and kind staff.');
        $make('Ben Meh', 'TBR-REV-B', 2, 'Room was hot at night.');
        $make('Cy Rude', 'TBR-REV-C', 1, 'Bad words here', true);

        $this->actingAs($staff);

        $page = $this->get(route('reviews.index'));
        $page->assertOk();
        $page->assertSeeInOrder(['Average rating', '2.7', 'From 3 reviews'], false);
        $page->assertSeeInOrder(['Blocked comments', '1'], false);
        $page->assertSee('1–2 stars to follow up');
        $page->assertSee('Profanity or vulgar language');
        $page->assertSee('3 reviews');

        $low = $this->get(route('reviews.index', ['rating' => 'low']));
        $low->assertSee('Ben Meh')->assertSee('Cy Rude')->assertDontSee('Ana Happy');
        $low->assertSee('2 reviews');

        $search = $this->get(route('reviews.index', ['search' => 'TBR-REV-A']));
        $search->assertSee('Ana Happy')->assertDontSee('Ben Meh');

        $byComment = $this->get(route('reviews.index', ['search' => 'hot at night']));
        $byComment->assertSee('Ben Meh')->assertDontSee('Ana Happy');
    }

    public function test_tourist_review_is_auto_published_and_profanity_is_auto_blocked(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Cottage 08']);
        $booking = $this->makeBooking($tourist, $unit, [
            'reference_no' => 'TBR-AUTO-REV',
            'status'       => 'completed',
        ]);

        $this->actingAs($tourist);

        // Submit review with bad word
        $response = $this->post('/tourist/reviews', [
            'booking_id' => $booking->id,
            'rating'     => 2,
            'comment'    => 'Pangit ng service tangina niyo!',
        ]);

        $response->assertRedirect(route('tourist.reviews'));

        // Review is auto-approved/published, star rating is 2, but comment is blocked!
        $this->assertDatabaseHas('reviews', [
            'booking_id'         => $booking->id,
            'rating'             => 2,
            'is_approved'        => true,
            'is_comment_blocked' => true,
        ]);
    }

    public function test_admin_can_export_pdf_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/reports/export-pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_export_excel_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/reports/export-excel');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_create_and_manage_accommodation_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->post('/accommodations', [
            'unit_number'     => 'Cottage 15',
            'unit_type'       => 'cottage',
            'variant'         => 'premium',
            'price_per_night' => 3500.00,
            'price_per_hour'  => 200.00,
            'max_occupancy'   => 15,
            'amenities'       => "Air-conditioned\nPrivate bathroom\nKaraoke system",
            'description'     => 'Spacious beachfront cottage with private dining area.',
        ]);

        $response->assertRedirect(route('accommodations.cottages'));
        $this->assertDatabaseHas('accommodation_units', [
            'unit_number' => 'Cottage 15',
            'variant'     => 'premium',
        ]);
    }

    public function test_new_room_number_must_not_already_be_taken(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['ROOM 1', 'Room 02'] as $name) {
            AccommodationUnit::create([
                'unit_number' => $name, 'unit_type' => 'room', 'variant' => 'normal',
                'price_per_night' => 1500, 'price_per_hour' => 125, 'max_occupancy' => 2, 'is_available' => true,
            ]);
        }
        $fields = ['unit_type' => 'room', 'variant' => 'normal', 'price_per_night' => 1500, 'price_per_hour' => 125, 'max_occupancy' => 2];

        $this->get(route('accommodations.index'))
            ->assertOk()
            ->assertSee('value="ROOM 3"', false);

        $this->post('/accommodations', $fields + ['unit_number' => 'ROOM 2'])
            ->assertSessionHasErrors(['unit_number' => 'ROOM 2 is already taken. The next free number is ROOM 3.']);
        $this->assertSame(2, AccommodationUnit::where('unit_type', 'room')->count());

        $this->post('/accommodations', $fields + ['unit_number' => '3'])
            ->assertRedirect(route('accommodations.index'));
        $this->assertDatabaseHas('accommodation_units', ['unit_number' => 'ROOM 3', 'unit_type' => 'room']);

        $room1 = AccommodationUnit::where('unit_number', 'ROOM 1')->first();
        $this->put("/accommodations/{$room1->id}", array_merge($fields, ['unit_number' => 'ROOM 1', 'price_per_night' => 1800]))
            ->assertSessionHasNoErrors();
        $this->assertSame('1800.00', (string) $room1->fresh()->price_per_night);

        $this->put("/accommodations/{$room1->id}", $fields + ['unit_number' => 'ROOM 3'])
            ->assertSessionHasErrors(['unit_number' => 'ROOM 3 is already taken. The next free number is ROOM 1.']);
        $this->assertSame('ROOM 1', $room1->fresh()->unit_number);
    }

    public function test_unit_with_only_finished_stays_can_be_deleted_and_keeps_its_bookings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $guest = User::factory()->create(['role' => 'tourist']);

        $cottage = $this->makeUnit(['unit_number' => 'COT6', 'unit_type' => 'cottage']);
        $past = $this->makeBooking($guest, $cottage, [
            'reference_no' => 'TBR-PASTPAID', 'status' => 'paid', 'stay_type' => 'hour',
            'check_in_date' => now()->subDays(3)->format('Y-m-d'), 'check_out_date' => now()->subDays(3)->format('Y-m-d'),
            'hour_start' => '11:00', 'hours_count' => 1,
        ]);

        $this->delete("/accommodations/{$cottage->id}")
            ->assertRedirect(route('accommodations.cottages'))
            ->assertSessionHas('success', 'Unit COT6 deleted.');
        $this->assertSoftDeleted('accommodation_units', ['id' => $cottage->id]);
        $this->assertSame('COT6', $past->fresh()->accommodationUnit->unit_number);
        $this->assertSame('paid', $past->fresh()->status);

        $room = $this->makeUnit(['unit_number' => 'ROOM 9']);
        $this->makeBooking($guest, $room, [
            'reference_no' => 'TBR-UPCOMING', 'status' => 'paid',
            'check_in_date' => now()->addDays(2)->format('Y-m-d'), 'check_out_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $this->delete("/accommodations/{$room->id}")
            ->assertSessionHas('error', 'ROOM 9 cannot be deleted while it has 1 upcoming or ongoing reservation (TBR-UPCOMING). Cancel or finish them first, or mark the unit unavailable.');
        $this->assertNotSoftDeleted('accommodation_units', ['id' => $room->id]);
    }

    public function test_admin_can_approve_special_resort_booking(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $guest  = User::factory()->create(['role' => 'tourist']);

        $booking = Booking::create([
            'reference_no'          => 'TBRS-TEST001',
            'user_id'               => $guest->id,
            'accommodation_unit_id' => null,
            'booking_date'          => now()->addDays(5)->format('Y-m-d'),
            'check_in_date'         => now()->addDays(5)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(7)->format('Y-m-d'),
            'nights_count'          => 2,
            'guests_count'          => 80,
            'status'                => 'pending',
            'booking_type'          => 'special_resort',
            'admin_approval_status' => 'pending',
            'total_amount'          => 20000.00,
        ]);

        $payment = Payment::create([
            'booking_id'      => $booking->id,
            'amount'          => 20000.00,
            'gateway'         => 'gcash',
            'payment_channel' => 'gcash',
            'status'          => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->post("/bookings/{$booking->id}/approve-special");

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id'                    => $booking->id,
            'admin_approval_status' => 'approved',
            'status'                => 'paid',
            'admin_approved_by'     => $admin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'success',
        ]);
    }

    public function test_admin_can_reject_special_resort_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guest = User::factory()->create(['role' => 'tourist']);

        $booking = Booking::create([
            'reference_no'          => 'TBRS-TEST002',
            'user_id'               => $guest->id,
            'accommodation_unit_id' => null,
            'booking_date'          => now()->addDays(10)->format('Y-m-d'),
            'guests_count'          => 50,
            'status'                => 'pending',
            'booking_type'          => 'special_resort',
            'admin_approval_status' => 'pending',
            'total_amount'          => 5000.00,
        ]);

        $this->actingAs($admin);

        $response = $this->post("/bookings/{$booking->id}/reject-special", [
            'reason' => 'Resort is undergoing private maintenance on those dates.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id'                    => $booking->id,
            'admin_approval_status' => 'rejected',
            'status'                => 'cancelled',
        ]);
    }

    public function test_admin_can_update_exclusive_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guest = User::factory()->create(['role' => 'tourist']);
        $this->makeUnit(['unit_number' => 'Room EX', 'price_per_night' => 1000.00]);

        $checkIn = now()->addDays(20)->format('Y-m-d');
        $checkOut = now()->addDays(21)->format('Y-m-d');
        $booking = Booking::create([
            'reference_no'          => 'TBRS-EDIT01',
            'user_id'               => $guest->id,
            'accommodation_unit_id' => null,
            'booking_date'          => $checkIn,
            'check_in_date'         => $checkIn,
            'check_out_date'        => $checkOut,
            'nights_count'          => 1,
            'guests_count'          => 40,
            'status'                => 'pending',
            'booking_type'          => 'special_resort',
            'admin_approval_status' => 'pending',
            'total_amount'          => 1000.00,
            'special_requests'      => 'Birthday setup',
        ]);
        Payment::create([
            'booking_id' => $booking->id,
            'amount'     => 1000.00,
            'gateway'    => 'cash',
            'status'     => 'pending',
        ]);

        $this->actingAs($admin);
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
        $newIn = now()->addDays(22)->toDateString();
        $newOut = now()->addDays(25)->toDateString();

        $response = $this->put("/bookings/{$booking->id}/exclusive", [
            'check_in'         => $newIn,
            'check_out'        => $newOut,
            'guests_count'     => 80,
            'special_requests' => 'Wedding setup',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', [
            'id'               => $booking->id,
            'nights_count'     => 3,
            'guests_count'     => 80,
            'total_amount'     => 3000.00,
            'special_requests' => 'Wedding setup',
            'status'           => 'pending',
        ]);
        $this->assertSame($newIn, $booking->fresh()->checkInDate()->toDateString());
        $this->assertSame($newOut, $booking->fresh()->checkOutDate()->toDateString());
    }

    public function test_admin_and_staff_can_manage_accommodations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create unit
        $response = $this->post('/accommodations', [
            'unit_number'     => 'Cottage 15',
            'unit_type'       => 'cottage',
            'variant'         => 'premium',
            'price_per_night' => 3500.00,
            'price_per_hour'  => 200.00,
            'max_occupancy'   => 15,
            'amenities'       => "Air-conditioned\nPrivate bathroom\nKaraoke system",
            'description'     => 'Spacious beachfront cottage with private dining area.',
        ]);

        $response->assertRedirect(route('accommodations.cottages'));
        $this->assertDatabaseHas('accommodation_units', [
            'unit_number' => 'Cottage 15',
            'variant'     => 'premium',
        ]);

        $unit = \App\Models\AccommodationUnit::where('unit_number', 'Cottage 15')->first();

        // Toggle availability
        $toggleRes = $this->post("/accommodations/{$unit->id}/toggle-availability");
        $toggleRes->assertRedirect();
        $this->assertDatabaseHas('accommodation_units', [
            'id'           => $unit->id,
            'is_available' => false,
        ]);
    }

    public function test_staff_approving_payment_creates_notification_for_tourist(): void
    {
        $staff   = $this->makeStaff();
        $guest   = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit(['unit_number' => 'Room 03', 'price_per_night' => 500.00]);
        $booking = $this->makeBooking($guest, $unit, [
            'reference_no' => 'TBR-NOTIFPAY',
            'guests_count' => 1,
            'total_amount' => 500.00,
        ]);
        $payment = Payment::create([
            'booking_id'     => $booking->id,
            'amount'         => 500.00,
            'gateway'        => 'gcash',
            'status'         => 'pending',
            'transaction_id' => 'GCASH-999',
        ]);

        $this->actingAs($staff);

        $response = $this->post("/payments/{$payment->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('notifications_table', [
            'user_id' => $guest->id,
            'type'    => 'payment',
        ]);
    }

    public function test_admin_can_update_user_active_status(): void
    {
        $admin      = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create([
            'role'      => 'tourist',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        // Deactivate user (is_active not provided/unchecked)
        $response = $this->put("/users/{$targetUser->id}", [
            'name'  => $targetUser->name,
            'email' => $targetUser->email,
            'role'  => 'tourist',
        ]);

        $response->assertRedirect(route('maintenance.section', 'accounts'));
        $this->assertDatabaseHas('users', [
            'id'        => $targetUser->id,
            'is_active' => false,
        ]);

        // Re-activate user
        $response2 = $this->put("/users/{$targetUser->id}", [
            'name'      => $targetUser->name,
            'email'     => $targetUser->email,
            'role'      => 'tourist',
            'is_active' => '1',
        ]);

        $response2->assertRedirect(route('maintenance.section', 'accounts'));
        $this->assertDatabaseHas('users', [
            'id'        => $targetUser->id,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_create_manual_booking_for_walk_in_guest(): void
    {
        $staff = $this->makeStaff();
        $unit  = $this->makeUnit(['price_per_night' => 1200.00]);

        $this->actingAs($staff);

        $bookingDate = now()->addDays(2)->format('Y-m-d');
        $checkOutDate = now()->addDays(4)->format('Y-m-d'); // 2 nights

        $response = $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'walk_in',
            'first_name'            => 'Maria',
            'middle_name'           => 'Cruz',
            'last_name'             => 'Santos',
            'guest_phone'           => '0917-987-6543',
            'guest_email'           => 'maria@example.com',
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => $bookingDate,
            'check_out_date'        => $checkOutDate,
            'guests_count'          => 2,
            'payment_method'        => 'cash',
            'initial_status'        => 'paid',
            'special_requests'      => 'Early check-in requested',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'booking_source'        => 'manual',
            'guest_name_manual'     => 'Maria Cruz Santos',
            'guest_contact_manual'  => '0917-987-6543 · maria@example.com',
            'accommodation_unit_id' => $unit->id,
            'nights_count'          => 2,
            'guests_count'          => 2,
            'status'                => 'paid',
            'total_amount'          => 2400.00,
        ]);

        $booking = Booking::where('guest_name_manual', 'Maria Cruz Santos')->first();
        $this->assertNotNull($booking);
        $this->assertNull($booking->user_id);
        $this->assertEquals('Maria Cruz Santos', $booking->guest_name);
        $this->assertTrue($booking->isManual());

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount'     => 2400.00,
            'status'     => 'success',
            'gateway'    => 'cash',
        ]);
    }

    private function multiUnitPayload(array $unitIds, int $guests): array
    {
        return [
            'guest_type'             => 'walk_in',
            'first_name'             => 'Group',
            'last_name'              => 'Leader',
            'guest_phone'            => '0917-555-0000',
            'accommodation_unit_ids' => $unitIds,
            'booking_date'           => now()->addDays(6)->format('Y-m-d'),
            'check_out_date'         => now()->addDays(8)->format('Y-m-d'),
            'guests_count'           => $guests,
            'payment_method'         => 'cash',
            'initial_status'         => 'pending',
        ];
    }

    public function test_manual_booking_can_reserve_several_units_at_once(): void
    {
        $staff = $this->makeStaff();
        $roomA = $this->makeUnit(['unit_number' => 'Room A', 'max_occupancy' => 4, 'price_per_night' => 1000.00]);
        $roomB = $this->makeUnit(['unit_number' => 'Room B', 'max_occupancy' => 4, 'price_per_night' => 800.00]);

        $this->actingAs($staff);

        $this->post(route('bookings.manual-store'), $this->multiUnitPayload([$roomA->id, $roomB->id], 6))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', ['accommodation_unit_id' => $roomA->id, 'guest_name_manual' => 'Group Leader', 'guests_count' => 4, 'total_amount' => 2000.00]);
        $this->assertDatabaseHas('bookings', ['accommodation_unit_id' => $roomB->id, 'guest_name_manual' => 'Group Leader', 'guests_count' => 2, 'total_amount' => 1600.00]);
    }

    public function test_multi_unit_manual_booking_saves_nothing_when_one_unit_is_taken(): void
    {
        $staff  = $this->makeStaff();
        $guest  = User::factory()->create(['role' => 'tourist']);
        $roomA  = $this->makeUnit(['unit_number' => 'Room A']);
        $roomB  = $this->makeUnit(['unit_number' => 'Room B']);
        $this->makeBooking($guest, $roomB, [
            'booking_date'   => now()->addDays(6)->format('Y-m-d'),
            'check_in_date'  => now()->addDays(6)->format('Y-m-d'),
            'check_out_date' => now()->addDays(8)->format('Y-m-d'),
            'nights_count'   => 2,
            'status'         => 'paid',
        ]);

        $this->actingAs($staff);

        $this->post(route('bookings.manual-store'), $this->multiUnitPayload([$roomA->id, $roomB->id], 2))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('bookings', ['guest_name_manual' => 'Group Leader']);
    }

    public function test_unit_availability_lists_units_booked_for_the_chosen_stay(): void
    {
        $staff = $this->makeStaff();
        $guest = User::factory()->create(['role' => 'tourist']);
        $roomA = $this->makeUnit(['unit_number' => 'Room A']);
        $roomB = $this->makeUnit(['unit_number' => 'Room B']);
        $day   = now()->addDays(6)->format('Y-m-d');
        $this->makeBooking($guest, $roomA, [
            'booking_date'   => $day,
            'check_in_date'  => $day,
            'check_out_date' => now()->addDays(8)->format('Y-m-d'),
            'nights_count'   => 2,
            'status'         => 'paid',
        ]);
        $this->makeBooking($guest, $roomB, [
            'booking_date'   => now()->addDays(10)->format('Y-m-d'),
            'check_in_date'  => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(11)->format('Y-m-d'),
            'nights_count'   => 0,
            'stay_type'      => 'hour',
            'hour_start'     => '10:00',
            'hours_count'    => 3,
            'time_slot'      => '10:00 AM – 1:00 PM (3 hours)',
            'status'         => 'paid',
        ]);

        $this->actingAs($staff);

        $nightly = $this->getJson(route('bookings.unit-availability', ['date' => $day, 'check_out' => now()->addDays(7)->format('Y-m-d')]))
            ->assertOk()->json('unavailable');
        $this->assertSame('Booked', $nightly[$roomA->id] ?? null);
        $this->assertArrayNotHasKey($roomB->id, $nightly);

        $later = $this->getJson(route('bookings.unit-availability', ['date' => now()->addDays(8)->format('Y-m-d'), 'check_out' => now()->addDays(9)->format('Y-m-d')]))
            ->json('unavailable');
        $this->assertEmpty($later);

        $hourDay = now()->addDays(10)->format('Y-m-d');
        $overlap = $this->getJson(route('bookings.unit-availability', ['date' => $hourDay, 'stay_type' => 'hour', 'hour_start' => '12:00', 'hours' => 2]))
            ->json('unavailable');
        $this->assertSame('Booked 10:00 AM – 1:00 PM', $overlap[$roomB->id] ?? null);

        $free = $this->getJson(route('bookings.unit-availability', ['date' => $hourDay, 'stay_type' => 'hour', 'hour_start' => '14:00', 'hours' => 2]))
            ->json('unavailable');
        $this->assertArrayNotHasKey($roomB->id, $free);
    }

    public function test_multi_unit_manual_booking_checks_total_capacity(): void
    {
        $staff = $this->makeStaff();
        $roomA = $this->makeUnit(['unit_number' => 'Room A', 'max_occupancy' => 2]);
        $roomB = $this->makeUnit(['unit_number' => 'Room B', 'max_occupancy' => 2]);

        $this->actingAs($staff);

        $this->post(route('bookings.manual-store'), $this->multiUnitPayload([$roomA->id, $roomB->id], 5))
            ->assertSessionHas('error');
        $this->post(route('bookings.manual-store'), $this->multiUnitPayload([$roomA->id, $roomB->id], 1))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('bookings', ['guest_name_manual' => 'Group Leader']);
    }

    public function test_walk_in_manual_booking_needs_names_and_a_contact(): void
    {
        $staff = $this->makeStaff();
        $unit  = $this->makeUnit();

        $this->actingAs($staff);

        $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'walk_in',
            'first_name'            => 'No',
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(3)->format('Y-m-d'),
            'guests_count'          => 1,
            'payment_method'        => 'cash',
            'initial_status'        => 'pending',
        ])->assertSessionHasErrors(['last_name', 'guest_phone', 'guest_email']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_staff_can_create_hourly_manual_booking(): void
    {
        $staff = $this->makeStaff();
        $unit  = $this->makeUnit(['price_per_night' => 1200.00, 'price_per_hour' => 150.00]);

        $this->actingAs($staff);

        $date = now()->addDays(3)->format('Y-m-d');

        $response = $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'walk_in',
            'first_name'            => 'Hourly',
            'last_name'             => 'Guest',
            'guest_phone'           => '0917-000-1111',
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => $date,
            'stay_type'             => 'hour',
            'hour_start'            => '10:00',
            'hours_count'           => 3,
            'guests_count'          => 1,
            'payment_method'        => 'cash',
            'initial_status'        => 'pending',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'guest_name_manual'     => 'Hourly Guest',
            'accommodation_unit_id' => $unit->id,
            'stay_type'             => 'hour',
            'hour_start'            => '10:00',
            'hours_count'           => 3,
            'nights_count'          => 0,
            'total_amount'          => 450.00,
        ]);
    }

    public function test_staff_exclusive_booking_saves_occasion(): void
    {
        $staff = $this->makeStaff();
        $this->makeUnit(['price_per_night' => 1000.00]);

        $this->actingAs($staff);

        $payload = [
            'guest_type'       => 'walk_in',
            'first_name'       => 'Event',
            'last_name'        => 'Organizer',
            'guest_phone'      => '0917-222-3333',
            'check_in'         => now()->addDays(10)->format('Y-m-d'),
            'check_out'        => now()->addDays(11)->format('Y-m-d'),
            'guests_count'     => 40,
            'payment_method'   => 'cash',
            'initial_status'   => 'pending',
            'special_requests' => 'Stage near the beach',
        ];

        $this->post(route('bookings.exclusive-store'), $payload)
            ->assertSessionHasErrors('occasion');

        $this->post(route('bookings.exclusive-store'), $payload + ['occasion' => 'Birthday'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'booking_type'      => 'special_resort',
            'guest_name_manual' => 'Event Organizer',
            'special_requests'  => "Occasion: Birthday\n\nStage near the beach",
        ]);
    }

    public function test_staff_can_create_manual_booking_for_registered_tourist(): void
    {
        $staff   = $this->makeStaff();
        $tourist = User::factory()->create(['role' => 'tourist', 'name' => 'Juan Dela Cruz']);
        $unit    = $this->makeUnit(['price_per_night' => 800.00]);

        $this->actingAs($staff);

        $bookingDate = now()->addDays(5)->format('Y-m-d');
        $checkOutDate = now()->addDays(6)->format('Y-m-d');

        $response = $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'registered',
            'user_id'               => $tourist->id,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => $bookingDate,
            'check_out_date'        => $checkOutDate,
            'guests_count'          => 1,
            'payment_method'        => 'gcash',
            'initial_status'        => 'paid',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'booking_source'        => 'manual',
            'user_id'               => $tourist->id,
            'accommodation_unit_id' => $unit->id,
            'status'                => 'paid',
            'total_amount'          => 800.00,
        ]);

        // Verify tourist was notified
        $this->assertDatabaseHas('notifications_table', [
            'user_id' => $tourist->id,
            'type'    => 'booking',
        ]);
    }

    public function test_manual_booking_detects_and_prevents_conflict(): void
    {
        $staff   = $this->makeStaff();
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit();

        $bookingDate = now()->addDays(10)->format('Y-m-d');
        $checkOutDate = now()->addDays(12)->format('Y-m-d');

        // Existing booking
        $this->makeBooking($tourist, $unit, [
            'booking_date'   => $bookingDate,
            'check_in_date'  => $bookingDate,
            'check_out_date' => $checkOutDate,
            'status'         => 'paid',
        ]);

        $this->actingAs($staff);

        // Try booking overlapping dates
        $response = $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'walk_in',
            'first_name'            => 'Conflict',
            'last_name'             => 'Guest',
            'guest_email'           => 'conflict@example.com',
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(11)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(13)->format('Y-m-d'),
            'guests_count'          => 2,
            'payment_method'        => 'cash',
            'initial_status'        => 'paid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('bookings', [
            'guest_name_manual' => 'Conflict Guest',
        ]);
    }

    public function test_tourist_cannot_access_manual_booking_store(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $unit    = $this->makeUnit();

        $this->actingAs($tourist);

        $response = $this->post(route('bookings.manual-store'), [
            'guest_type'            => 'walk_in',
            'first_name'            => 'Hacker',
            'last_name'             => 'Guest',
            'guest_phone'           => '09000000000',
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->addDays(20)->format('Y-m-d'),
            'check_out_date'        => now()->addDays(21)->format('Y-m-d'),
            'guests_count'          => 1,
            'payment_method'        => 'cash',
            'initial_status'        => 'paid',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_tour_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $asset = \App\Models\TourAsset::create([
            'title'         => 'Original Beach Entrance',
            'description'   => 'Original description',
            'panorama_path' => 'tour-assets/original.jpg',
            'type'          => 'image',
            'hotspots'      => [],
            'sort_order'    => 1,
            'is_active'     => true,
        ]);

        $this->actingAs($admin);

        $response = $this->put(route('tour.manage.update', $asset), [
            'title'       => 'Updated Beachfront Scene',
            'description' => 'Updated viewpoint description',
            'type'        => 'image',
            'sort_order'  => 3,
            'is_active'   => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tour_assets', [
            'id'          => $asset->id,
            'title'       => 'Updated Beachfront Scene',
            'description' => 'Updated viewpoint description',
            'sort_order'  => 3,
            'is_active'   => 0,
        ]);
    }

    public function test_tour_page_shows_scene_status_and_flags_flat_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        \App\Models\TourAsset::create(['title' => 'Lobby Photo', 'panorama_path' => 'tour-assets/missing-lobby.jpg', 'type' => 'image', 'hotspots' => [], 'sort_order' => 1, 'is_active' => true]);
        \App\Models\TourAsset::create(['title' => 'Old Pool Clip', 'panorama_path' => 'tour-assets/missing-pool.mp4', 'type' => 'video', 'hotspots' => [], 'sort_order' => 2, 'is_active' => false]);

        $this->actingAs($admin)->get(route('tour.manage'))
            ->assertOk()
            ->assertSee('Lobby Photo')
            ->assertSee('Flat photo')
            ->assertSee('1 show as flat photos')
            ->assertSee('Old Pool Clip')
            ->assertSee('Hidden')
            ->assertSee('tour-row-hidden', false);
    }

    public function test_add_scene_window_reopens_with_typed_values_when_upload_fails(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('tour.manage'))
            ->followingRedirects()
            ->post(route('tour.manage.store'), [
                '_form'      => 'add_scene',
                'title'      => 'Sunset Deck',
                'type'       => 'image',
                'sort_order' => 4,
            ]);

        $response->assertOk()
            ->assertSee('data-reopen="1"', false)
            ->assertSee('unit-modal-error', false)
            ->assertSee('value="Sunset Deck"', false);
        $this->assertDatabaseMissing('tour_assets', ['title' => 'Sunset Deck']);
    }

    public function test_public_tour_lists_only_shown_scenes_in_order(): void
    {
        \App\Models\TourAsset::create(['title' => 'Second Scene', 'panorama_path' => 'tour-assets/missing-2.jpg', 'type' => 'image', 'hotspots' => [], 'sort_order' => 2, 'is_active' => true]);
        \App\Models\TourAsset::create(['title' => 'First Scene', 'panorama_path' => 'tour-assets/missing-1.jpg', 'type' => 'image', 'hotspots' => [], 'sort_order' => 1, 'is_active' => true]);
        \App\Models\TourAsset::create(['title' => 'Secret Scene', 'panorama_path' => 'tour-assets/missing-3.jpg', 'type' => 'image', 'hotspots' => [], 'sort_order' => 3, 'is_active' => false]);

        $this->get(route('tour.viewer'))
            ->assertOk()
            ->assertSeeInOrder(['First Scene', 'Second Scene'])
            ->assertSee('2 scenes')
            ->assertDontSee('Secret Scene');
    }

    public function test_cannot_create_second_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Attempting to create a second admin account must fail
        $response = $this->post(route('users.store'), [
            'name'                  => 'Second Admin',
            'email'                 => 'admin2@talisay.com',
            'phone'                 => '09123456789',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'admin2@talisay.com',
        ]);

        // Creating a staff account should succeed
        $responseStaff = $this->post(route('users.store'), [
            'name'                  => 'New Frontdesk Staff',
            'email'                 => 'staffnew@talisay.com',
            'phone'                 => '09123456789',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'staff',
        ]);

        $responseStaff->assertRedirect(route('maintenance.section', 'accounts'));
        $this->assertDatabaseHas('users', [
            'email' => 'staffnew@talisay.com',
            'role'  => 'staff',
        ]);
    }

    public function test_add_staff_window_reopens_with_typed_values_when_saving_fails(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        User::factory()->create(['email' => 'taken@talisay.com']);
        $this->actingAs($admin);

        $page = $this->get(route('tasks.staff'));
        $page->assertOk();
        $page->assertSee('Create staff account');
        $page->assertDontSee('data-reopen="1"', false);

        $response = $this->from(route('tasks.staff'))->followingRedirects()->post(route('tasks.staff.store'), [
            '_form'          => 'add_staff',
            'first_name'     => 'Rosa',
            'last_name'      => 'Typed',
            'email'          => 'taken@talisay.com',
            'preset'         => 'cashier',
            'password'       => 'Kx7pQ2mN9vRt',
            'modules'        => ['payments' => ['enabled' => '1', 'actions' => ['view', 'verify']]],
        ]);

        $response->assertOk();
        $response->assertSee('data-reopen="1"', false);
        $response->assertSee('The account was not created.');
        $response->assertSee('value="Rosa"', false);
        $response->assertSee('name="preset" id="add-preset" value="cashier"', false);
        $response->assertDontSee('id="add-position"', false);
        $this->assertDatabaseMissing('users', ['name' => 'Rosa Typed']);
    }

    public function test_add_staff_joins_the_name_parts_and_assigns_the_staff_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);
        $expectedId = User::nextStaffId();

        $this->post(route('tasks.staff.store'), [
            '_form'          => 'add_staff',
            'first_name'     => 'Maria',
            'middle_name'    => 'Lopez',
            'last_name'      => 'Santos',
            'email'          => 'maria.santos@talisay.com',
            'preset'         => 'cashier',
            'password'       => 'Kx7pQ2mN9vRt',
            'modules'        => ['payments' => ['enabled' => '1', 'actions' => ['view', 'verify']]],
        ])->assertRedirect()->assertSessionHas('success');

        $staff = User::where('email', 'maria.santos@talisay.com')->firstOrFail();
        $this->assertSame('Maria Lopez Santos', $staff->name);
        $this->assertSame($expectedId, $staff->staff_id);
        $this->assertSame('Billing & Cashier Specialist', $staff->position);
        $this->assertSame('Finance & Billing', $staff->department);
        $this->assertSame('active', $staff->account_status);
        $this->assertSame('available', $staff->duty_status);
        $this->assertTrue((bool) $staff->is_active);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Kx7pQ2mN9vRt', $staff->password));
        $this->assertSame(['payments'], $staff->permissions()->pluck('module')->all());

        $this->post(route('tasks.staff.store'), [
            'first_name'     => 'Nolast',
            'email'          => 'nolast@talisay.com',
            'preset'         => 'housekeeper',
            'password'       => 'Kx7pQ2mN9vRt',
        ])->assertSessionHasErrors('last_name');

        $this->post(route('tasks.staff.store'), [
            'first_name' => 'No',
            'last_name'  => 'Job',
            'email'      => 'nojob@talisay.com',
            'password'   => 'Kx7pQ2mN9vRt',
        ])->assertSessionHasErrors('preset');
    }

    public function test_a_job_that_already_has_a_staff_member_cannot_be_given_to_another(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $holder = User::factory()->create([
            'role'       => 'staff',
            'name'       => 'Ana Desk',
            'position'   => 'Front Desk Officer',
            'department' => 'Front Office',
        ]);
        $other = User::factory()->create([
            'role'       => 'staff',
            'position'   => 'Housekeeping Attendant',
            'department' => 'Housekeeping',
        ]);
        $this->actingAs($admin);

        $page = $this->get(route('tasks.staff'));
        $page->assertOk();
        $page->assertSee('Taken by Ana Desk');
        $page->assertSee('Filled by Ana Desk');
        $page->assertSee('Edit access');
        $page->assertSee('Save access');
        $page->assertSee('data-preset="front_desk" disabled', false);
        $page->assertDontSee('Role Preset Auto-Fill');
        $page->assertDontSee('Position / Job Title');

        $this->post(route('tasks.staff.store'), [
            'first_name' => 'Second',
            'last_name'  => 'Desk',
            'email'      => 'second.desk@talisay.com',
            'preset'     => 'front_desk',
            'password'   => 'Kx7pQ2mN9vRt',
        ])->assertSessionHasErrors(['preset' => 'Ana Desk is already the Front Desk Officer. Each job can only have one staff member.']);
        $this->assertDatabaseMissing('users', ['email' => 'second.desk@talisay.com']);

        $this->from(route('tasks.staff'))->put(route('tasks.staff.permissions', $other), [
            'position'       => 'front desk officer',
            'department'     => 'Front Office',
            'account_status' => 'active',
        ])->assertSessionHas('error');
        $this->assertSame('Housekeeping Attendant', $other->fresh()->position);

        $this->from(route('tasks.staff'))->put(route('tasks.staff.permissions', $holder), [
            'staff_id'       => $holder->staff_id,
            'position'       => 'Front Desk Officer',
            'department'     => 'Front Office',
            'account_status' => 'active',
        ])->assertSessionHas('success');
    }
}


