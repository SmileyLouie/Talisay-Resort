<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopySqliteToPgsql extends Command
{
    protected $signature = 'db:copy-sqlite-to-pgsql {--fresh : Wipe destination tables before copy}';

    protected $description = 'Copy all data from the sqlite connection into the default pgsql connection';

    public function handle(): int
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('Set DB_CONNECTION=pgsql before running this command.');

            return self::FAILURE;
        }

        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);

        try {
            DB::connection('sqlite')->getPdo();
            DB::connection('pgsql')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Could not connect: '.$e->getMessage());

            return self::FAILURE;
        }

        $tables = collect(DB::connection('sqlite')->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        ))->pluck('name')->values();

        if ($tables->isEmpty()) {
            $this->warn('No SQLite tables found.');

            return self::SUCCESS;
        }

        // Prefer parents before dependents when possible.
        $preferred = [
            'users', 'password_reset_tokens', 'sessions',
            'accommodation_units', 'unit_variants', 'amenity_items', 'payment_methods',
            'resort_activities', 'bookings', 'payments', 'reviews',
            'tour_assets', 'gallery_photos', 'chatbot_logs', 'chatbot_configs',
            'system_settings', 'notifications', 'personal_access_tokens',
            'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks',
        ];
        $ordered = collect($preferred)->filter(fn ($t) => $tables->contains($t))
            ->merge($tables->reject(fn ($t) => in_array($t, $preferred, true)))
            ->values();

        DB::connection('pgsql')->statement('SET session_replication_role = replica');

        foreach ($ordered as $table) {
            if (! Schema::connection('pgsql')->hasTable($table)) {
                $this->warn("Skip missing pgsql table: {$table}");
                continue;
            }

            if ($this->option('fresh')) {
                DB::connection('pgsql')->table($table)->delete();
            }

            $rows = DB::connection('sqlite')->table($table)->get();
            if ($rows->isEmpty()) {
                $this->line("{$table}: 0 rows");
                continue;
            }

            $chunk = [];
            foreach ($rows as $row) {
                $chunk[] = (array) $row;
                if (count($chunk) >= 200) {
                    DB::connection('pgsql')->table($table)->insert($chunk);
                    $chunk = [];
                }
            }
            if ($chunk !== []) {
                DB::connection('pgsql')->table($table)->insert($chunk);
            }

            $this->info("{$table}: {$rows->count()} rows");

            if (Schema::connection('pgsql')->hasColumn($table, 'id')) {
                $max = (int) DB::connection('pgsql')->table($table)->max('id');
                if ($max > 0) {
                    $seq = $table.'_id_seq';
                    try {
                        DB::connection('pgsql')->statement("SELECT setval(?, ?, true)", [$seq, $max]);
                    } catch (\Throwable) {
                        // Some tables may not use a serial sequence.
                    }
                }
            }
        }

        DB::connection('pgsql')->statement('SET session_replication_role = DEFAULT');
        $this->info('Copy finished.');

        return self::SUCCESS;
    }
}
