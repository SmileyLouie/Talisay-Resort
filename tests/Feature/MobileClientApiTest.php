<?php

namespace Tests\Feature;

use App\Models\AccommodationUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileClientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_load_units(): void
    {
        AccommodationUnit::create([
            'unit_number' => 'Cottage 01',
            'unit_type' => 'cottage',
            'variant' => 'premium',
            'max_occupancy' => 6,
            'price_per_night' => 2500,
            'is_available' => true,
            'description' => 'Beach cottage',
        ]);

        $registered = $this->postJson('/api/mobile/register', [
            'name' => 'Ana Cruz',
            'email' => 'ana.mobile@example.com',
            'phone' => '09170000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registered->assertCreated();
        $token = $registered->json('token');
        $this->assertNotEmpty($token);
        $this->assertSame('tourist', User::where('email', 'ana.mobile@example.com')->value('role'));

        $this->getJson('/api/mobile/units')
            ->assertOk()
            ->assertJsonPath('units.0.unit_number', 'Cottage 01');

        $this->withToken($token)->getJson('/api/user')->assertOk();
    }

    public function test_guest_can_check_whether_dates_are_open(): void
    {
        $unit = AccommodationUnit::create([
            'unit_number' => 'Room 01',
            'unit_type' => 'room',
            'variant' => 'normal',
            'max_occupancy' => 4,
            'price_per_night' => 1500,
            'is_available' => true,
        ]);

        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $this->getJson('/api/mobile/units/'.$unit->id.'/availability?check_in='.$checkIn.'&check_out='.$checkOut)
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('nights', 2)
            ->assertJsonPath('total_amount', 3000);

        $unit->update(['is_available' => false]);

        $this->getJson('/api/mobile/units/'.$unit->id.'/availability?check_in='.$checkIn.'&check_out='.$checkOut)
            ->assertOk()
            ->assertJsonPath('available', false);
    }

    public function test_staff_cannot_use_the_guest_login(): void
    {
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'staff@example.com',
            'password' => 'password123',
        ])->assertStatus(422);
    }
}
