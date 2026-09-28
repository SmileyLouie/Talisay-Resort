<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->unique();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->unique();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->unique();
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 40);
            $table->unsignedBigInteger('mysql_id')->nullable();
            $table->string('action', 40);
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['processed_at', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
