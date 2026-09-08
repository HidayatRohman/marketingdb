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
            $table->unsignedBigInteger('sumber_id')->nullable()->after('label_id');
            $table->foreign('sumber_id')->references('id')->on('sumbers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            $table->dropForeign(['sumber_id']);
            $table->dropColumn('sumber_id');
        });
    }
};
