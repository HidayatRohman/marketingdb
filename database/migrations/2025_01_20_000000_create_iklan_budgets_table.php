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
        Schema::create('iklan_budgets', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal'); // Tanggal budget
            $table->decimal('budget_amount', 15, 2)->default(0); // Budget yang dialokasikan
            $table->decimal('spent_amount', 15, 2)->default(0); // Spent (budget yang sudah terpakai)
            $table->decimal('spent_plus_tax', 15, 2)->default(0); // Spent + Tax
            $table->integer('real_lead')->default(0); // Real Lead (jumlah lead yang didapat)
            $table->decimal('cost_per_lead', 15, 2)->default(0); // Cost/Lead (spent/real_lead)
            $table->integer('closing')->default(0); // Jumlah closing
            $table->decimal('omset', 15, 2)->default(0); // Omset dari transaksi
            $table->decimal('roas', 8, 4)->default(0); // ROAS (Return on Ad Spend) = omset/spent
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('tanggal');
            $table->unique('tanggal'); // Satu record per tanggal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iklan_budgets');
    }
};