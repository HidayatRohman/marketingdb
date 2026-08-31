<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BrandOwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first brand for assignment (must run after BrandSeeder)
        $firstBrand = Brand::first();

        // Buat user Brand Owner utama
        User::firstOrCreate(
            ['email' => 'brandowner@marketingdb.com'],
            [
                'name' => 'Brand Owner',
                'password' => Hash::make('password'),
                'role' => 'brand_owner',
                'brand_id' => $firstBrand?->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Brand Owner users seeded successfully!');
    }
}
