<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accommodation_units', function (Blueprint $table) {
            $table->string('variant', 40)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_channel', 40)->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_units', function (Blueprint $table) {
            $table->enum('variant', ['normal', 'premium'])->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_channel', ['gcash', 'paypal', 'card', 'cash'])->default('gcash')->change();
        });
    }
};
