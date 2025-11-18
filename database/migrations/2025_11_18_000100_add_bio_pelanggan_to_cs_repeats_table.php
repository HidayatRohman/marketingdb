<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cs_repeats') && !Schema::hasColumn('cs_repeats', 'bio_pelanggan')) {
            Schema::table('cs_repeats', function (Blueprint $table) {
                $table->text('bio_pelanggan')->nullable()->after('no_tlp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cs_repeats') && Schema::hasColumn('cs_repeats', 'bio_pelanggan')) {
            Schema::table('cs_repeats', function (Blueprint $table) {
                $table->dropColumn('bio_pelanggan');
            });
        }
    }
};
