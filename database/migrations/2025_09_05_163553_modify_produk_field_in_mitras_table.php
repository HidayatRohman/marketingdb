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
        Schema::table('mitras', function (Blueprint $table) {
            // Drop the old produk column
            $table->dropColumn('produk');
            
            // Add brand_id as foreign key
            $table->unsignedBigInteger('brand_id')->after('no_telp');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            // Drop foreign key and brand_id column
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
            
            // Add back the old produk column
            $table->string('produk')->after('no_telp');
        });
    }
};
