<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        // Existing confirmed payments: the last change is the best record of when they were approved.
        $now = now()->format('Y-m-d H:i:s');
        DB::table('payments')
            ->where('status', 'success')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw("CASE WHEN updated_at IS NOT NULL AND updated_at <= '{$now}' THEN updated_at ELSE created_at END")]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
