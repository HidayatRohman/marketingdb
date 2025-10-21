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
        if (!Schema::hasTable('transaksis')) {
            return;
        }

        if (!Schema::hasColumn('transaksis', 'nama_mitra')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->string('nama_mitra')->nullable()->after('usia');
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

        if (Schema::hasColumn('transaksis', 'nama_mitra')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropColumn('nama_mitra');
            });
        }
    }
};
