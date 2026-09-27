<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_number');                  // e.g., "Room 01", "Cottage 03"
            $table->enum('unit_type', ['room', 'cottage']);
            $table->enum('variant', ['normal', 'premium']);
            $table->decimal('floor_area_sqm', 6, 1)->nullable();
            $table->string('bed_configuration')->nullable();
            $table->integer('max_occupancy')->default(2);
            $table->json('amenities')->nullable();          // array of amenity strings
            $table->text('description')->nullable();
            $table->json('images')->nullable();             // array of image paths
            $table->string('tour_video_path')->nullable();  // uploaded MP4/WebM
            $table->decimal('price_per_night', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('unit_type');
            $table->index('variant');
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_units');
    }
};
