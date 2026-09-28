<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('booking_source')->default('online')->after('booking_type');
            $table->string('guest_name_manual')->nullable()->after('booking_source');
            $table->string('guest_contact_manual')->nullable()->after('guest_name_manual');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_source', 'guest_name_manual', 'guest_contact_manual']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
