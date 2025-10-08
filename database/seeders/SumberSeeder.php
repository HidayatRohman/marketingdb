<?php

namespace Database\Seeders;

use App\Models\Sumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sumbers = [
            ['nama' => 'Facebook', 'warna' => '#1877F2'],
            ['nama' => 'Instagram', 'warna' => '#E4405F'],
            ['nama' => 'WhatsApp', 'warna' => '#25D366'],
            ['nama' => 'TikTok', 'warna' => '#000000'],
            ['nama' => 'Google Ads', 'warna' => '#4285F4'],
            ['nama' => 'YouTube', 'warna' => '#FF0000'],
            ['nama' => 'Website', 'warna' => '#6B7280'],
            ['nama' => 'Referral', 'warna' => '#10B981'],
            ['nama' => 'Offline', 'warna' => '#8B5CF6'],
        ];

        foreach ($sumbers as $sumber) {
            Sumber::firstOrCreate(
                ['nama' => $sumber['nama']],
                ['warna' => $sumber['warna']]
            );
        }
    }
}
