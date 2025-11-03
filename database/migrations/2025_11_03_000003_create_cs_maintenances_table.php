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
        Schema::create('cs_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pelanggan');
            $table->string('no_tlp');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('chat')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->text('kendala')->nullable();
            $table->text('solusi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_maintenances');
    }
};