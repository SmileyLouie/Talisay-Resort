<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_comment_blocked')->default(false)->after('comment');
            $table->string('block_reason')->nullable()->after('is_comment_blocked');
            $table->timestamp('comment_blocked_at')->nullable()->after('block_reason');
        });

        // Set all existing reviews to approved since reviews are now automatically approved
        DB::table('reviews')->update(['is_approved' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_comment_blocked', 'block_reason', 'comment_blocked_at']);
        });
    }
};
