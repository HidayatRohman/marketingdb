<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds system-related tables that may need initial data.
     */
    public function run(): void
    {
        $this->seedSessions();
        $this->seedCacheEntries();
        $this->command->info('System tables seeder completed successfully!');
    }

    /**
     * Clear old sessions (cleanup)
     */
    private function seedSessions(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('sessions')) {
            return;
        }

        // Clean up old sessions (older than 30 days)
        DB::table('sessions')
            ->where('last_activity', '<', Carbon::now()->subDays(30)->timestamp)
            ->delete();

        $this->command->info('Sessions table cleaned up.');
    }

    /**
     * Clear old cache entries (cleanup)
     */
    private function seedCacheEntries(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('cache')) {
            return;
        }

        // Clear expired cache entries
        DB::table('cache')
            ->where('expiration', '<', Carbon::now()->timestamp)
            ->delete();

        $this->command->info('Cache table cleaned up.');
    }
}