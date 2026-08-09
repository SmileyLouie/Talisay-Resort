<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->date('booking_date');
            $table->string('time_slot')->nullable();
            $table->integer('guests_count')->default(1);
            $table->enum('status', ['pending', 'paid', 'checked_in', 'checked_out', 'cancelled', 'completed'])->default('pending');
            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_date', 'status']);
            $table->index('user_id');
            $table->index('package_id');
        });

        Schema::create('capacity_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('max_capacity')->default(100);
            $table->integer('current_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_schedules');
        Schema::dropIfExists('bookings');
    }
};
