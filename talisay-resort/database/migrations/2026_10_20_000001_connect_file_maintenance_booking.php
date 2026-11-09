<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_variants', function (Blueprint $table) {
            $table->decimal('price_per_night', 10, 2)->nullable()->after('label');
        });

        Schema::table('resort_activities', function (Blueprint $table) {
            $table->boolean('is_bookable')->default(true)->after('is_active');
        });

        Schema::create('booking_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resort_activity_id')->nullable()->nullOnDelete();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activities');
        Schema::table('resort_activities', function (Blueprint $table) {
            $table->dropColumn('is_bookable');
        });
        Schema::table('unit_variants', function (Blueprint $table) {
            $table->dropColumn('price_per_night');
        });
    }
};
