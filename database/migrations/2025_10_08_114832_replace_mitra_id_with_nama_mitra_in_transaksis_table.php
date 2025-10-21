<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transaksis')) {
            return;
        }

        // Drop FK on mitra_id if exists, then drop column
        if (Schema::hasColumn('transaksis', 'mitra_id')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaksis' AND COLUMN_NAME = 'mitra_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            foreach ($constraints as $row) {
                $name = $row->CONSTRAINT_NAME ?? $row->constraint_name ?? null;
                if ($name) {
                    DB::statement("ALTER TABLE `transaksis` DROP FOREIGN KEY `{$name}`");
                }
            }
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropColumn('mitra_id');
            });
        }

        // Add nama_mitra string column if not exists
        if (!Schema::hasColumn('transaksis', 'nama_mitra')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->string('nama_mitra')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transaksis')) {
            return;
        }

        // Drop nama_mitra if exists
        if (Schema::hasColumn('transaksis', 'nama_mitra')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropColumn('nama_mitra');
            });
        }

        // Restore mitra_id FK if not exists
        if (!Schema::hasColumn('transaksis', 'mitra_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('mitra_id')->after('user_id')->constrained()->onDelete('cascade');
            });
        }
    }
};
