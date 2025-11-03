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
        Schema::create('cs_repeats', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pelanggan');
            $table->string('no_tlp');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('chat')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->decimal('transaksi', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_repeats');
    }
};