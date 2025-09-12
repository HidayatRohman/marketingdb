<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if site_settings table exists
        if (!DB::getSchemaBuilder()->hasTable('site_settings')) {
            $this->command->warn('site_settings table does not exist. Skipping SiteSettingsSeeder.');
            return;
        }

        $siteSettings = [
            [
                'key' => 'site_name',
                'value' => 'Marketing Database System',
                'type' => 'text',
                'description' => 'Nama aplikasi yang ditampilkan di header dan title',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_name',
                'value' => 'Partner Bisnismu',
                'type' => 'text',
                'description' => 'Nama perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_address',
                'value' => 'Jl. Bisnis Raya No. 123, Jakarta Pusat, DKI Jakarta 10110',
                'type' => 'textarea',
                'description' => 'Alamat lengkap perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_phone',
                'value' => '+62 21 1234 5678',
                'type' => 'text',
                'description' => 'Nomor telepon perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_email',
                'value' => 'info@partnerbisnismu.com',
                'type' => 'text',
                'description' => 'Email resmi perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_website',
                'value' => 'https://partnerbisnismu.com',
                'type' => 'url',
                'description' => 'Website resmi perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'text',
                'description' => 'Timezone default untuk sistem',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_file_upload_mb',
                'value' => '10',
                'type' => 'text',
                'description' => 'Maksimal ukuran file upload dalam MB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pagination_per_page',
                'value' => '30',
                'type' => 'text',
                'description' => 'Jumlah data per halaman pada tabel',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_email_notifications',
                'value' => 'true',
                'type' => 'text',
                'description' => 'Aktifkan notifikasi email (true/false)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'text',
                'description' => 'Mode maintenance aplikasi (true/false)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'theme_color',
                'value' => 'indigo',
                'type' => 'text',
                'description' => 'Tema warna utama aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_version',
                'value' => '1.0.0',
                'type' => 'text',
                'description' => 'Versi aplikasi saat ini',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_environment',
                'value' => 'production',
                'type' => 'text',
                'description' => 'Environment aplikasi (production/development/staging)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($siteSettings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('SiteSettings seeder completed successfully!');
    }
}