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

    public function test_client_and_staff_cannot_login_on_admin_portal(): void
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

        $this->assertGuest();
        $responseStaff->assertSessionHasErrors('email');

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

    public function test_staff_and_tourist_can_login_on_client_portal(): void
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
            'portal'   => 'client',
        ]);

        $responseTourist->assertRedirect(route('tourist.dashboard'));
        $this->assertAuthenticatedAs($tourist);
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
            'max_occupancy'   => 15,
            'amenities'       => "Air-conditioned\nPrivate bathroom\nKaraoke system",
            'description'     => 'Spacious beachfront cottage with private dining area.',
        ]);

        $response->assertRedirect(route('accommodations.index'));
        $this->assertDatabaseHas('accommodation_units', [
            'unit_number' => 'Cottage 15',
            'variant'     => 'premium',
        ]);
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
            'max_occupancy'   => 15,
            'amenities'       => "Air-conditioned\nPrivate bathroom\nKaraoke system",
            'description'     => 'Spacious beachfront cottage with private dining area.',
        ]);

        $response->assertRedirect(route('accommodations.index'));
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

        $response->assertRedirect(route('users.index'));
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

        $response2->assertRedirect(route('users.index'));
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
            'guest_name'            => 'Maria Santos',
            'guest_contact'         => '0917-987-6543',
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
            'guest_name_manual'     => 'Maria Santos',
            'guest_contact_manual'  => '0917-987-6543',
            'accommodation_unit_id' => $unit->id,
            'nights_count'          => 2,
            'guests_count'          => 2,
            'status'                => 'paid',
            'total_amount'          => 2400.00,
        ]);

        $booking = Booking::where('guest_name_manual', 'Maria Santos')->first();
        $this->assertNotNull($booking);
        $this->assertNull($booking->user_id);
        $this->assertEquals('Maria Santos', $booking->guest_name);
        $this->assertTrue($booking->isManual());

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount'     => 2400.00,
            'status'     => 'success',
            'gateway'    => 'cash',
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
            'guest_name'            => 'Conflict Guest',
            'guest_contact'         => '09170000000',
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
            'guest_name'            => 'Hacker Guest',
            'guest_contact'         => '09000000000',
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

        $responseStaff->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'staffnew@talisay.com',
            'role'  => 'staff',
        ]);
    }
}


