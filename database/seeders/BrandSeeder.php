<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['nama' => 'Brand A'],
            ['nama' => 'Brand B'],
            ['nama' => 'Brand C'],
            ['nama' => 'Produk Premium'],
            ['nama' => 'Produk Standard'],
            ['nama' => 'Produk Ekonomis'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['nama' => $brand['nama']]);
        }
    }
}
