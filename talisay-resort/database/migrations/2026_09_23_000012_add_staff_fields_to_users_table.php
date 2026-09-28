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
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('role');
            $table->string('department')->nullable()->after('position');
            $table->enum('duty_status', ['available', 'busy', 'on_leave', 'off_duty'])
                  ->default('available')
                  ->after('department');
            $table->text('duty_notes')->nullable()->after('duty_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['position', 'department', 'duty_status', 'duty_notes']);
        });
    }
};
