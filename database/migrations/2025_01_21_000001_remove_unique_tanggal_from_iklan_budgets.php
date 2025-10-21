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
        if (!Schema::hasTable('iklan_budgets')) {
            return;
        }

        $uniqueExists = !empty(DB::select("SHOW INDEX FROM iklan_budgets WHERE Key_name = 'iklan_budgets_tanggal_unique'"));

        if ($uniqueExists) {
            Schema::table('iklan_budgets', function (Blueprint $table) {
                $table->dropUnique(['tanggal']); // Remove unique constraint on tanggal jika ada
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('iklan_budgets')) {
            return;
        }

        $uniqueExists = !empty(DB::select("SHOW INDEX FROM iklan_budgets WHERE Key_name = 'iklan_budgets_tanggal_unique'"));

        if (!$uniqueExists) {
            Schema::table('iklan_budgets', function (Blueprint $table) {
                $table->unique('tanggal'); // Tambahkan kembali unique jika belum ada
            });
        }
    }
};