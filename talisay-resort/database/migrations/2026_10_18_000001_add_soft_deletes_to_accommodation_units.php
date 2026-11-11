<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bookings cascade when a unit row is removed, so deleting a unit only archives it
     * and its past reservations, payments, and reports keep pointing at it.
     */
    public function up(): void
    {
        Schema::table('accommodation_units', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_units', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
