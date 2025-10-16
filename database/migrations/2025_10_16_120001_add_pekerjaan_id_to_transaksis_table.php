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
        Schema::table('transaksis', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksis', 'pekerjaan_id')) {
                $table->unsignedBigInteger('pekerjaan_id')->nullable()->after('nama_mitra');
                $table->foreign('pekerjaan_id')->references('id')->on('pekerjaans')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('transaksis', 'pekerjaan_id')) {
                $table->dropForeign(['pekerjaan_id']);
                $table->dropColumn('pekerjaan_id');
            }
        });
    }
};