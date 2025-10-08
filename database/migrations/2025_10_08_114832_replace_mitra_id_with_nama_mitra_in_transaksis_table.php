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
            // Drop foreign key constraint first
            $table->dropForeign(['mitra_id']);
            // Drop the mitra_id column
            $table->dropColumn('mitra_id');
            // Add nama_mitra as string
            $table->string('nama_mitra')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Drop nama_mitra column
            $table->dropColumn('nama_mitra');
            // Add back mitra_id with foreign key
            $table->foreignId('mitra_id')->after('user_id')->constrained()->onDelete('cascade');
        });
    }
};
