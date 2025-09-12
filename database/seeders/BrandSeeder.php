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
            [
                'nama' => 'Partner Bisnismu',
                'logo' => 'partner-bisnismu-logo.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Digital Marketing Pro',
                'logo' => 'digital-marketing-pro.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Business Solution',
                'logo' => 'business-solution.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Smart Marketing',
                'logo' => 'smart-marketing.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Growth Accelerator',
                'logo' => 'growth-accelerator.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Enterprise Hub',
                'logo' => 'enterprise-hub.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Startup Booster',
                'logo' => 'startup-booster.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Market Leader',
                'logo' => 'market-leader.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['nama' => $brand['nama']], 
                $brand
            );
        }
    }
}
