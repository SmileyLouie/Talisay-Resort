<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasIndex('bookings', 'bookings_conflict_lookup_idx')) {
                $table->index(['accommodation_unit_id', 'status', 'check_in_date', 'check_out_date'], 'bookings_conflict_lookup_idx');
            }
            if (!Schema::hasIndex('bookings', 'bookings_booking_type_index')) {
                $table->index('booking_type');
            }
        });

        if (Schema::hasTable('reviews')) {
            $keepIds = DB::table('reviews')
                ->selectRaw('MAX(id) as id')
                ->groupBy('booking_id')
                ->pluck('id');

            if ($keepIds->isNotEmpty()) {
                DB::table('reviews')->whereNotIn('id', $keepIds)->delete();
            }

            if (!Schema::hasIndex('reviews', 'reviews_booking_id_unique')) {
                Schema::table('reviews', function (Blueprint $table) {
                    $table->unique('booking_id');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_conflict_lookup_idx');
            $table->dropIndex(['booking_type']);
        });
    }
};
