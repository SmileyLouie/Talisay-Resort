<?php

namespace Tests\Feature;

use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffRbacManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    private function createStaff(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role'           => 'staff',
            'is_active'      => true,
            'account_status' => 'active',
            'duty_status'    => 'available',
            'position'       => 'Front Desk Officer',
            'department'     => 'Front Office',
            'staff_id'       => 'TBR-STF-099',
        ], $attributes));
    }

    public function test_user_permission_helper_methods_for_admin_and_staff(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        // Admin has universal access
        $this->assertTrue($admin->hasModuleAccess('bookings'));
        $this->assertTrue($admin->hasModuleAccess('payments'));
        $this->assertTrue($admin->hasPermission('bookings', 'delete'));
        $this->assertTrue($admin->hasPermission('payments', 'refund'));

        // Staff with no permissions has no access
        $this->assertFalse($staff->hasModuleAccess('bookings'));
        $this->assertFalse($staff->hasPermission('bookings', 'view'));

        // Assign bookings module with view and create
        StaffPermission::create([
            'user_id' => $staff->id,
            'module'  => 'bookings',
            'actions' => ['view', 'create'],
        ]);

        $staff->refresh();
        $this->assertTrue($staff->hasModuleAccess('bookings'));
        $this->assertTrue($staff->hasPermission('bookings', 'view'));
        $this->assertTrue($staff->hasPermission('bookings', 'create'));
        $this->assertFalse($staff->hasPermission('bookings', 'delete'));
        $this->assertFalse($staff->hasModuleAccess('payments'));
    }

    public function test_suspended_or_inactive_staff_has_no_permissions(): void
    {
        $staff = $this->createStaff([
            'account_status' => 'suspended',
        ]);

        StaffPermission::create([
            'user_id' => $staff->id,
            'module'  => 'bookings',
            'actions' => ['view', 'create'],
        ]);

        $this->assertFalse($staff->hasModuleAccess('bookings'));
        $this->assertFalse($staff->hasPermission('bookings', 'view'));

        // Inactive account
        $staff->update(['account_status' => 'inactive']);
        $this->assertFalse($staff->hasModuleAccess('bookings'));

        // Re-activated account
        $staff->update(['account_status' => 'active']);
        $this->assertTrue($staff->hasModuleAccess('bookings'));
    }

    public function test_check_permission_middleware_blocks_unauthorized_staff(): void
    {
        $staff = $this->createStaff();
        $this->actingAs($staff);

        // Web requests redirect to staff.dashboard with error message
        $response = $this->get(route('bookings.index'));
        $response->assertRedirect(route('staff.dashboard'));
        $response->assertSessionHas('error');

        $payResponse = $this->get(route('payments.index'));
        $payResponse->assertRedirect(route('staff.dashboard'));
        $payResponse->assertSessionHas('error');

        // JSON requests receive 403 Forbidden
        $jsonResponse = $this->getJson(route('bookings.index'));
        $jsonResponse->assertStatus(403);
    }

    public function test_check_permission_middleware_allows_authorized_staff(): void
    {
        $staff = $this->createStaff();
        StaffPermission::create([
            'user_id' => $staff->id,
            'module'  => 'bookings',
            'actions' => ['view', 'create'],
        ]);

        $this->actingAs($staff);

        $response = $this->get(route('bookings.index'));
        $response->assertStatus(200);

        // Still blocked from unauthorized payments module
        $payResponse = $this->get(route('payments.index'));
        $payResponse->assertRedirect(route('staff.dashboard'));
        $payResponse->assertSessionHas('error');
    }

    public function test_admin_can_access_staff_management_index_tabs(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();
        $this->actingAs($admin);

        // Roster tab
        $resRoster = $this->get(route('tasks.staff', ['tab' => 'roster']));
        $resRoster->assertStatus(200);
        $resRoster->assertSee($staff->name);

        // RBAC tab
        $resRbac = $this->get(route('tasks.staff', ['tab' => 'rbac']));
        $resRbac->assertStatus(200);
        $resRbac->assertSee('Job &amp; Role Assignments', false);

        // Activity tab
        $resAct = $this->get(route('tasks.staff', ['tab' => 'activity']));
        $resAct->assertStatus(200);
        $resAct->assertSee('Staff Activity Log');
    }

    public function test_non_admin_cannot_access_staff_management(): void
    {
        $staff = $this->createStaff();
        $this->actingAs($staff);

        $response = $this->get(route('tasks.staff'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_staff_account_with_rbac_permissions(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $response = $this->post(route('tasks.staff.store'), [
            'name'                  => 'New Front Desk Agent',
            'email'                 => 'agent@talisayresort.com',
            'phone'                 => '09123456789',
            'staff_id'              => 'TBR-STF-010',
            'position'              => 'Front Desk Officer',
            'department'            => 'Front Office',
            'account_status'        => 'active',
            'duty_status'           => 'available',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'modules'               => [
                'bookings' => [
                    'enabled' => '1',
                    'actions' => ['view', 'create', 'confirm'],
                ],
                'accommodations' => [
                    'enabled' => '1',
                    'actions' => ['view'],
                ],
            ],
        ]);

        $response->assertRedirect(route('tasks.staff', ['tab' => 'rbac']));
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'agent@talisayresort.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('staff', $newUser->role);
        $this->assertEquals('TBR-STF-010', $newUser->staff_id);
        $this->assertEquals('Front Desk Officer', $newUser->position);
        $this->assertEquals('Front Office', $newUser->department);
        $this->assertTrue($newUser->hasModuleAccess('bookings'));
        $this->assertTrue($newUser->hasPermission('bookings', 'confirm'));
        $this->assertFalse($newUser->hasPermission('bookings', 'delete'));
        $this->assertTrue($newUser->hasModuleAccess('accommodations'));
        $this->assertFalse($newUser->hasModuleAccess('payments'));
    }

    public function test_admin_can_update_staff_permissions_and_job_assignment(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff();

        $this->actingAs($admin);

        $response = $this->from(route('tasks.staff', ['tab' => 'rbac']))->put(route('tasks.staff.permissions', $staff), [
            'staff_id'       => 'TBR-STF-777',
            'position'       => 'Cashier Specialist',
            'department'     => 'Finance & Billing',
            'account_status' => 'active',
            'modules'        => [
                'payments' => [
                    'enabled' => '1',
                    'actions' => ['view', 'verify'],
                ],
            ],
        ]);

        $response->assertRedirect(route('tasks.staff', ['tab' => 'rbac']));
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertEquals('TBR-STF-777', $staff->staff_id);
        $this->assertEquals('Cashier Specialist', $staff->position);
        $this->assertEquals('Finance & Billing', $staff->department);
        $this->assertTrue($staff->hasModuleAccess('payments'));
        $this->assertTrue($staff->hasPermission('payments', 'verify'));
        $this->assertFalse($staff->hasPermission('payments', 'refund'));
        $this->assertFalse($staff->hasModuleAccess('bookings'));
    }

    public function test_admin_can_update_staff_account_status(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff(['account_status' => 'active']);

        $this->actingAs($admin);

        $response = $this->patch(route('tasks.staff.account-status', $staff), [
            'account_status' => 'suspended',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertEquals('suspended', $staff->account_status);
        $this->assertFalse($staff->is_active);
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('tasks.staff.reset-password', $staff), [
            'password'              => 'NewSecurePass999!',
            'password_confirmation' => 'NewSecurePass999!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertTrue(Hash::check('NewSecurePass999!', $staff->password));
    }

    public function test_admin_can_update_staff_duty_status(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff(['duty_status' => 'available']);

        $this->actingAs($admin);

        $response = $this->patch(route('tasks.staff.duty', $staff), [
            'duty_status' => 'busy',
            'duty_notes'  => 'Assigned to poolside maintenance inspection',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertEquals('busy', $staff->duty_status);
        $this->assertEquals('Assigned to poolside maintenance inspection', $staff->duty_notes);
    }

    public function test_staff_dashboard_renders_only_assigned_module_widgets(): void
    {
        $staff = $this->createStaff();
        StaffPermission::create([
            'user_id' => $staff->id,
            'module'  => 'bookings',
            'actions' => ['view'],
        ]);

        $this->actingAs($staff);

        $response = $this->get(route('staff.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Welcome back, ' . $staff->name);
        $response->assertSee('Booking Operations Overview');
        // Should NOT see Payment verification widget
        $response->assertDontSee('Payment Verification Queue');
    }
}
