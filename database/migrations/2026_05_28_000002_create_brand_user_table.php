<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand_user')) {
            Schema::create('brand_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->primary(['user_id', 'brand_id']);
            });
        }

        if (Schema::hasColumn('users', 'brand_id')) {
            DB::table('users')
                ->whereNotNull('brand_id')
                ->orderBy('id')
                ->chunkById(500, function ($users) {
                    foreach ($users as $user) {
                        DB::table('brand_user')->updateOrInsert([
                            'user_id' => $user->id,
                            'brand_id' => $user->brand_id,
                        ], []);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_user');
    }
};
