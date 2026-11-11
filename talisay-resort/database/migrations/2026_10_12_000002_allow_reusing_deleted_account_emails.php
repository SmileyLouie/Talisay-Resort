<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (['email', 'staff_id', 'external_id', 'google_id', 'facebook_id'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            $index = "users_{$column}_unique";

            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS "'.$index.'"');
                DB::statement('CREATE UNIQUE INDEX "'.$index.'" ON "users" ("'.$column.'") WHERE "deleted_at" IS NULL');
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS "'.$index.'"');
                DB::statement('CREATE UNIQUE INDEX "'.$index.'" ON "users" ("'.$column.'") WHERE "deleted_at" IS NULL');
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (['email', 'staff_id', 'external_id', 'google_id', 'facebook_id'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            $index = "users_{$column}_unique";

            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS "'.$index.'"');
                DB::statement('CREATE UNIQUE INDEX "'.$index.'" ON "users" ("'.$column.'")');
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS "'.$index.'"');
                DB::statement('CREATE UNIQUE INDEX "'.$index.'" ON "users" ("'.$column.'")');
            }
        }
    }
};
