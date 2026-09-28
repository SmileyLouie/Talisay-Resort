<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add staff_id and account_status to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_id')->nullable()->unique()->after('id');
            $table->enum('account_status', ['active', 'inactive', 'on_leave', 'suspended'])
                  ->default('active')
                  ->after('is_active');
        });

        // 2. Create staff_permissions table for granular RBAC
        Schema::create('staff_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('module', 50); // e.g. bookings, payments, accommodations, housekeeping, maintenance, tasks, reviews, tour, reports, chatbot, emergency
            $table->json('actions')->nullable(); // e.g. ["view", "create", "edit", "confirm", "cancel", "check_in_out", "delete"]
            $table->timestamps();

            $table->unique(['user_id', 'module']);
            $table->index(['user_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_permissions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_id', 'account_status']);
        });
    }
};
