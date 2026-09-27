<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemSettingsUserAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_accounts_under_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Boss', 'email' => 'adminboss@talisay.com']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Staff Maria', 'email' => 'staffmaria@talisay.com']);
        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Guest Juan', 'email' => 'guestjuan@talisay.com']);

        $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'users']));

        $response->assertOk();
        $response->assertViewIs('settings.index');
        $response->assertSee('User Accounts Management');
        $response->assertSee('Admin Boss');
        $response->assertSee('Staff Maria');
        $response->assertSee('Guest Juan');
        $response->assertSee('Total Users');
    }

    public function test_admin_can_filter_users_by_role_and_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Elena Frontdesk', 'email' => 'elena@talisay.com']);
        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Carlos Tourist', 'email' => 'carlos@gmail.com']);

        // Search Elena
        $searchResponse = $this->actingAs($admin)->get(route('settings.index', [
            'tab'    => 'users',
            'search' => 'Elena',
        ]));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Elena Frontdesk');
        $searchResponse->assertDontSee('Carlos Tourist');

        // Filter by role tourist
        $roleResponse = $this->actingAs($admin)->get(route('settings.index', [
            'tab'  => 'users',
            'role' => 'tourist',
        ]));
        $roleResponse->assertOk();
        $roleResponse->assertSee('Carlos Tourist');
        $roleResponse->assertDontSee('Elena Frontdesk');
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guest = User::factory()->create(['role' => 'tourist', 'is_active' => true, 'name' => 'Juan Santos']);

        $this->actingAs($admin);

        // Suspend user
        $response = $this->patch(route('users.toggle-status', $guest));
        $response->assertRedirect(route('settings.index', ['tab' => 'users']));

        $guest->refresh();
        $this->assertFalse($guest->is_active);

        // Reactivate user
        $response2 = $this->patch(route('users.toggle-status', $guest));
        $response2->assertRedirect(route('settings.index', ['tab' => 'users']));

        $guest->refresh();
        $this->assertTrue($guest->is_active);
    }

    public function test_admin_cannot_deactivate_or_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Main Admin']);
        $this->actingAs($admin);

        // Cannot toggle self
        $toggleResponse = $this->patch(route('users.toggle-status', $admin));
        $toggleResponse->assertSessionHas('error');
        $admin->refresh();
        $this->assertTrue($admin->is_active);

        // Cannot delete self
        $deleteResponse = $this->delete(route('users.destroy', $admin));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'password' => Hash::make('oldpassword123')]);

        $this->actingAs($admin);

        $response = $this->post(route('users.reset-password', $staff), [
            'password'              => 'newsecurepassword123',
            'password_confirmation' => 'newsecurepassword123',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'users']));
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertTrue(Hash::check('newsecurepassword123', $staff->password));
    }

    public function test_admin_can_delete_tourist_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guest = User::factory()->create(['role' => 'tourist']);

        $this->actingAs($admin);

        $response = $this->delete(route('users.destroy', $guest));
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $guest->id]);
    }

    public function test_users_index_url_redirects_to_settings_user_accounts_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/users');
        $response->assertRedirect(route('settings.index', ['tab' => 'users']));
    }

    public function test_non_admin_cannot_access_settings_user_accounts(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $guest = User::factory()->create(['role' => 'tourist']);

        $staffResponse = $this->actingAs($staff)->get(route('settings.index', ['tab' => 'users']));
        $staffResponse->assertForbidden();

        $guestResponse = $this->actingAs($guest)->get(route('settings.index', ['tab' => 'users']));
        $guestResponse->assertForbidden();
    }
}
