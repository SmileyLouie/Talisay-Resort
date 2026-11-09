<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemSettingsUserAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
    }

    public function test_admin_can_view_user_accounts_under_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Boss', 'email' => 'adminboss@talisay.com']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Staff Maria', 'email' => 'staffmaria@talisay.com']);
        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Guest Juan', 'email' => 'guestjuan@talisay.com']);

        $response = $this->actingAs($admin)->get(route('maintenance.section', 'accounts'));

        $response->assertOk();
        $response->assertViewIs('maintenance.index');
        $response->assertSee('User accounts');
        $response->assertSee('talisaySidebar', false);
        $response->assertSee('sidebar-is-closed', false);
        $response->assertSee('Admin Boss');
        $response->assertSee('Staff Maria');
        $response->assertSee('Guest Juan');
        $response->assertSee('Total users');
    }

    public function test_admin_can_filter_users_by_role_and_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Elena Frontdesk', 'email' => 'elena@talisay.com']);
        $guest = User::factory()->create(['role' => 'tourist', 'name' => 'Carlos Tourist', 'email' => 'carlos@gmail.com']);

        // Search Elena
        $searchResponse = $this->actingAs($admin)->get(route('maintenance.section', [
            'section' => 'accounts',
            'search'  => 'Elena',
        ]));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Elena Frontdesk');
        $searchResponse->assertDontSee('Carlos Tourist');

        // Filter by role tourist
        $roleResponse = $this->actingAs($admin)->get(route('maintenance.section', [
            'section' => 'accounts',
            'role'    => 'tourist',
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
        $response->assertRedirect(route('maintenance.section', 'accounts'));

        $guest->refresh();
        $this->assertFalse($guest->is_active);

        // Reactivate user
        $response2 = $this->patch(route('users.toggle-status', $guest));
        $response2->assertRedirect(route('maintenance.section', 'accounts'));

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

        $response->assertRedirect(route('maintenance.section', 'accounts'));
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
        $response->assertRedirect(route('maintenance.section', 'accounts'));
    }

    public function test_settings_stays_visible_and_drops_the_master_files_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'account_status' => 'active']);

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('sidebar-foot', false);
        $response->assertSee('>Settings<', false);
        $response->assertDontSee('System Settings');
        $response->assertDontSee('Master files');
    }

    public function test_appearance_language_applies_across_the_site(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'account_status' => 'active']);

        $page = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'appearance']));
        $page->assertOk();
        $page->assertSee('Appearance');
        $page->assertSee('This browser only.');
        $page->assertSee('id="adminTheme"', false);
        $page->assertSee('name="settings[ui_locale]"', false);
        $page->assertDontSee('name="talisayTheme"', false);

        $saved = $this->actingAs($admin)->put(route('settings.save'), [
            'settings' => ['ui_locale' => 'fil'],
        ]);
        $saved->assertRedirect();

        $ignored = $this->actingAs($admin)->put(route('settings.save'), [
            'settings' => ['ui_locale' => 'fr'],
        ]);
        $ignored->assertRedirect();

        $filipino = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'appearance']));
        $filipino->assertOk();
        $filipino->assertSee('Mga setting');
        $filipino->assertSee('Itsura');
        $filipino->assertSee('Wika');

        auth()->logout();

        $guest = $this->get(route('public.about'));
        $guest->assertOk();
        $guest->assertSee('Mag-log in');
        $guest->assertSee('Mga kuwarto');

        $rooms = $this->actingAs($admin)->get(route('accommodations.index'));
        $rooms->assertOk();
        $rooms->assertSee('Magdagdag ng kuwarto');
        $rooms->assertSee('Bakante');
    }

    public function test_non_admin_cannot_access_settings_user_accounts(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $guest = User::factory()->create(['role' => 'tourist']);

        $staffResponse = $this->actingAs($staff)->get(route('maintenance.section', 'accounts'));
        $staffResponse->assertForbidden();

        $guestResponse = $this->actingAs($guest)->get(route('maintenance.section', 'accounts'));
        $guestResponse->assertForbidden();
    }
}
