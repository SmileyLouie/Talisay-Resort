<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('gemini');
            $table->string('model')->default('gemini-1.5-flash');
            $table->text('api_key')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->text('system_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_tokens')->default(600);
            $table->boolean('is_enabled')->default(true);
            $table->string('response_language')->default('en');
            $table->string('personality')->default('friendly');
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_configs');
    }
};
