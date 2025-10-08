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
        Schema::table('iklan_budgets', function (Blueprint $table) {
            $table->dropUnique(['tanggal']); // Remove unique constraint on tanggal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iklan_budgets', function (Blueprint $table) {
            $table->unique('tanggal'); // Add back unique constraint on tanggal
        });
    }
};