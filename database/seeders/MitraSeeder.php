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
                'transaksi' => 5000000.00,
                'komentar' => 'Mitra terpercaya dengan volume transaksi tinggi',
            ],
            [
                'nama' => 'CV Berkah Jaya',
                'no_telp' => '082345678901',
                'brand_id' => $brandIds[1] ?? $brandIds[0],
                'chat' => 'followup',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
                'transaksi' => 3500000.00,
                'komentar' => 'Membutuhkan followup untuk peningkatan penjualan',
            ],
            [
                'nama' => 'Toko Makmur',
                'no_telp' => '083456789012',
                'brand_id' => $brandIds[2] ?? $brandIds[0],
                'chat' => 'masuk',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'transaksi' => 2750000.00,
                'komentar' => 'Mitra baru dengan potensi berkembang',
            ],
        ];

        foreach ($mitras as $mitra) {
            Mitra::create($mitra);
        }

        $this->command->info('Mitra seeder completed successfully!');
    }
}
