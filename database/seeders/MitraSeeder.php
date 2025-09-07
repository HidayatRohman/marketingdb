<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mitra;
use App\Models\Brand;

class MitraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada brand yang tersedia
        $brandIds = Brand::pluck('id')->toArray();
        
        if (empty($brandIds)) {
            $this->command->error('No brands found. Please run BrandSeeder first.');
            return;
        }

        // Buat beberapa mitra dengan brand yang sudah ada
        $mitras = [
            [
                'nama' => 'PT Mitra Sejahtera',
                'no_telp' => '081234567890',
                'brand_id' => $brandIds[0],
                'chat' => 'masuk',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'komentar' => 'Mitra terpercaya dengan potensi tinggi',
                'tanggal_lead' => now()->format('Y-m-d'),
            ],
            [
                'nama' => 'CV Berkah Jaya',
                'no_telp' => '082345678901',
                'brand_id' => $brandIds[1] ?? $brandIds[0],
                'chat' => 'followup',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
                'komentar' => 'Membutuhkan followup untuk peningkatan penjualan',
                'tanggal_lead' => now()->subDays(1)->format('Y-m-d'),
            ],
            [
                'nama' => 'Toko Makmur',
                'no_telp' => '083456789012',
                'brand_id' => $brandIds[2] ?? $brandIds[0],
                'chat' => 'masuk',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'komentar' => 'Mitra baru dengan potensi berkembang',
                'tanggal_lead' => now()->subDays(2)->format('Y-m-d'),
            ],
        ];

        foreach ($mitras as $mitra) {
            Mitra::create($mitra);
        }

        $this->command->info('Mitra seeder completed successfully!');
    }
}
