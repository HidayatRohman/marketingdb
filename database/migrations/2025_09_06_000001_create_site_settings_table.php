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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'textarea', 'file', 'url'])->default('text');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('site_settings')->insert([
            [
                'key' => 'site_title',
                'value' => 'Laravel Starter Kit',
                'type' => 'text',
                'description' => 'Title aplikasi yang ditampilkan di browser',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_description',
                'value' => 'Marketing Database Management System',
                'type' => 'textarea',
                'description' => 'Deskripsi singkat tentang aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'file',
                'description' => 'Logo utama aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'file',
                'description' => 'Favicon aplikasi (format .ico, .png)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
