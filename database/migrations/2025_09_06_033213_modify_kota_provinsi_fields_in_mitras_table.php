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
            $table->string('kota')->nullable()->default('Unknown')->change();
            $table->string('provinsi')->nullable()->default('Unknown')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            $table->string('kota')->nullable(false)->default(null)->change();
            $table->string('provinsi')->nullable(false)->default(null)->change();
        });
    }
};
