<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('stay_type')->default('night');
            $table->unsignedSmallInteger('hours_count')->nullable();
            $table->string('hour_start', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['stay_type', 'hours_count', 'hour_start']);
        });
    }
};
