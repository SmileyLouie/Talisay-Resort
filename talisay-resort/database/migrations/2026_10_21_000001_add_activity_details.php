<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resort_activities', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('schedule');
            $table->string('meeting_point')->nullable()->after('duration');
            $table->text('included')->nullable()->after('description');
            $table->text('what_to_bring')->nullable()->after('included');
            $table->unsignedInteger('max_guests')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('resort_activities', function (Blueprint $table) {
            $table->dropColumn(['duration', 'meeting_point', 'included', 'what_to_bring', 'max_guests']);
        });
    }
};
