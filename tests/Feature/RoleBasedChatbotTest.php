<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock GeminiAIService to isolate and test deterministic chatbot role logic
        $this->mock(\App\Services\GeminiAIService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });
    }

    private function makeUnit(): AccommodationUnit
    {
        return AccommodationUnit::create([
            'unit_number'     => 'Cottage 1',
            'unit_type'       => 'cottage',
            'variant'         => 'normal',
            'max_occupancy'   => 8,
            'price_per_night' => 1200.00,
            'is_available'    => true,
            'amenities'       => ['Sea View', 'Grill'],
            'description'     => 'Beachfront cottage',
        ]);
    }

    // ── GUEST TESTS ──────────────────────────────────────────────────────────

    public function test_guest_receives_guest_context(): void
    {
        $response = $this->getJson('/api/chatbot/context');

        $response->assertOk()
            ->assertJsonPath('role', 'guest')
            ->assertJsonStructure(['role', 'welcome_message', 'chips', 'mode_label']);
    }

    public function test_guest_can_ask_for_room_rates(): void
    {
        $this->makeUnit();

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'How much are the room rates?'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'guest')
            ->assertJsonPath('intent', 'rates');
        
        $this->assertStringContainsString('Talisay Beach Resort Accommodation Rates', $response->json('response'));
    }

    public function test_guest_asking_for_personal_booking_is_prompted_to_login(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Can you check my booking?'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'guest')
            ->assertJsonPath('intent', 'auth_required');

        $this->assertStringContainsString('log in', strtolower($response->json('response')));
    }

    // ── TOURIST TESTS ────────────────────────────────────────────────────────

    public function test_tourist_receives_personalized_context(): void
    {
        $tourist = User::factory()->create([
            'name' => 'Maria Santos',
            'role' => 'tourist',
        ]);

        $response = $this->actingAs($tourist)->getJson('/api/chatbot/context');

        $response->assertOk()
            ->assertJsonPath('role', 'tourist')
            ->assertJsonPath('name', 'Maria Santos');

        $this->assertStringContainsString('Maria', $response->json('welcome_message'));
    }

    public function test_tourist_can_query_own_bookings(): void
    {
        $unit = $this->makeUnit();
        $tourist = User::factory()->create(['name' => 'John Doe', 'role' => 'tourist']);

        Booking::create([
            'reference_no'          => 'TBR-MYBOOK',
            'user_id'               => $tourist->id,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => now()->format('Y-m-d'),
            'check_in_date'         => now()->format('Y-m-d'),
            'check_out_date'        => now()->addDay()->format('Y-m-d'),
            'nights_count'          => 1,
            'guests_count'          => 2,
            'status'                => 'confirmed',
            'total_amount'          => 1200.00,
        ]);

        $response = $this->actingAs($tourist)->postJson('/api/chatbot/message', [
            'message' => 'Show my bookings'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'tourist')
            ->assertJsonPath('intent', 'my_bookings');

        $this->assertStringContainsString('TBR-MYBOOK', $response->json('response'));
    }

    public function test_tourist_blocked_from_admin_data(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);

        $response = $this->actingAs($tourist)->postJson('/api/chatbot/message', [
            'message' => 'show all users and total revenue'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'tourist')
            ->assertJsonPath('intent', 'permission_denied');

        $this->assertStringContainsString('only accessible to resort administrators', $response->json('response'));
    }

    // ── STAFF TESTS ──────────────────────────────────────────────────────────

    public function test_staff_can_view_operational_status(): void
    {
        $staff = User::factory()->create(['name' => 'Staff Agent', 'role' => 'staff']);

        $response = $this->actingAs($staff)->postJson('/api/chatbot/message', [
            'message' => "today's bookings"
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'staff')
            ->assertJsonPath('intent', 'staff_reservations');

        $this->assertStringContainsString("Today's Reservations", $response->json('response'));
    }

    public function test_staff_blocked_from_admin_financial_actions(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->postJson('/api/chatbot/message', [
            'message' => 'delete user and total revenue report'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'staff')
            ->assertJsonPath('intent', 'permission_denied');

        $this->assertStringContainsString('**Administrator** access', $response->json('response'));
    }

    // ── ADMIN TESTS ──────────────────────────────────────────────────────────

    public function test_admin_can_access_management_summary(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/chatbot/message', [
            'message' => 'dashboard summary'
        ]);

        $response->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('intent', 'admin_summary');

        $this->assertStringContainsString('Resort Dashboard Summary', $response->json('response'));
    }

    // ── PAGE WIDGET RENDERING TESTS ──────────────────────────────────────────

    public function test_landing_page_renders_chatbot_widget(): void
    {
        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('landingChatbotFab')
            ->assertSee('landingChatbotCard')
            ->assertSee('Talisay Assistant')
            ->assertDontSee('landing-chatbot-dot');
    }

    public function test_login_page_renders_chatbot_widget(): void
    {
        $response = $this->get('/login');
        $response->assertOk()
            ->assertSee('guestChatbotFab')
            ->assertSee('guestChatbotCard')
            ->assertSee('Talisay Assistant');
    }

    public function test_register_page_renders_chatbot_widget(): void
    {
        $response = $this->get('/register');
        $response->assertOk()
            ->assertSee('guestChatbotFab')
            ->assertSee('guestChatbotCard')
            ->assertSee('Talisay Assistant');
    }

    public function test_tourist_portal_renders_chatbot_widget(): void
    {
        $tourist = User::factory()->create(['role' => 'tourist']);
        $response = $this->actingAs($tourist)->get('/tourist/dashboard');
        $response->assertOk()
            ->assertSee('touristChatbot()')
            ->assertSee('Talisay AI Assistant');
    }

    public function test_staff_portal_renders_chatbot_widget(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $response = $this->actingAs($staff)->get('/staff/dashboard');
        $response->assertOk()
            ->assertSee('chatbot-bubble')
            ->assertSee('chatbot-panel')
            ->assertSee('Talisay Assistant')
            ->assertDontSee('chatbot-notif-dot');
    }

    public function test_admin_portal_renders_chatbot_widget(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk()
            ->assertSee('chatbot-bubble')
            ->assertSee('chatbot-panel')
            ->assertSee('Talisay Assistant')
            ->assertDontSee('chatbot-notif-dot');
    }
}
