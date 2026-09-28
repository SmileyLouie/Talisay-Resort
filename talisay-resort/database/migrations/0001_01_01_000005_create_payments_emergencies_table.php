<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('gateway')->default('gcash');
            $table->enum('payment_channel', ['gcash', 'paypal', 'card', 'cash'])->default('gcash');
            $table->string('transaction_id')->nullable();
            $table->string('paypal_order_id')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->boolean('is_cash_on_arrival')->default(false);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->string('proof_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
