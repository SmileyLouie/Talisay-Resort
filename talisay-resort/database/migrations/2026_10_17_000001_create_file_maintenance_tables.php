<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resort_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('schedule')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('icon')->default('bi-water');
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('unit_variants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 80);
            $table->string('applies_to', 20)->default('both');
            $table->boolean('is_deluxe')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('slug');
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40);
            $table->string('name', 80);
            $table->string('kind', 20);
            $table->text('instructions')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('code');
        });

        Schema::create('amenity_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('unit_variants')->insert([
            ['slug' => 'normal', 'label' => 'Normal', 'applies_to' => 'both', 'is_deluxe' => 0, 'is_system' => 1, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'premium', 'label' => 'Deluxe', 'applies_to' => 'both', 'is_deluxe' => 1, 'is_system' => 1, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('payment_methods')->insert([
            ['code' => 'gcash', 'name' => 'GCash', 'kind' => 'ewallet', 'instructions' => 'Pay the resort GCash account, then upload the 13-digit reference and receipt.', 'account_name' => null, 'account_number' => null, 'icon' => 'bi-phone-fill', 'is_system' => 1, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'cash', 'name' => 'Cash on arrival', 'kind' => 'cash', 'instructions' => 'Pay in full at the front desk when you check in.', 'account_name' => null, 'account_number' => null, 'icon' => 'bi-cash-stack', 'is_system' => 1, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'card', 'name' => 'Card', 'kind' => 'card', 'instructions' => 'The resort records the last 4 digits of the card. The full number is not stored.', 'account_name' => null, 'account_number' => null, 'icon' => 'bi-credit-card-fill', 'is_system' => 1, 'is_active' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('amenity_items')->insert([
            ['name' => 'Air-conditioning', 'icon' => 'bi-snow', 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Private bathroom with hot shower', 'icon' => 'bi-droplet', 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Free Wi-Fi', 'icon' => 'bi-wifi', 'is_active' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Smart TV', 'icon' => 'bi-tv', 'is_active' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('resort_activities')->insert([
            ['title' => 'Snorkeling', 'description' => 'Guided snorkeling along the shore, with gear provided at the resort.', 'schedule' => 'Daily, 8:00 AM – 4:00 PM', 'price' => 350, 'icon' => 'bi-water', 'image_path' => null, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Island hopping', 'description' => 'A boat trip to nearby coves. Group size and weather decide the day’s route.', 'schedule' => 'By arrangement', 'price' => 1500, 'icon' => 'bi-tsunami', 'image_path' => null, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_items');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('unit_variants');
        Schema::dropIfExists('resort_activities');
    }
};
