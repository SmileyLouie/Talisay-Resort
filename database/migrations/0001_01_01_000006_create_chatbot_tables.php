<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->text('response');
            $table->enum('category', ['rates', 'hours', 'policies', 'directions', 'facilities', 'booking_help', 'general'])->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id');
            $table->text('message');
            $table->text('response');
            $table->string('intent')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_logs');
        Schema::dropIfExists('chatbot_intents');
    }
};
