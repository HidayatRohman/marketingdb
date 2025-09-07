<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_title',
                'value' => 'Marketing Database System',
                'description' => 'Judul aplikasi yang ditampilkan di header',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_description',
                'value' => 'Sistem manajemen database marketing untuk mengelola mitra dan leads',
                'description' => 'Deskripsi aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_name',
                'value' => 'Marketing Database Co.',
                'description' => 'Nama perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_email',
                'value' => 'admin@marketingdb.com',
                'description' => 'Email kontak untuk support',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Site settings seeded successfully!');
    }
}
