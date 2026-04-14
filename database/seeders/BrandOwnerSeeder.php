<?php

namespace Database\Seeders;

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
        User::updateOrCreate(
            ['email' => 'bo@marketingdb.com'],
            [
                'name' => 'Brand Owner',
                'password' => Hash::make('password'),
                'role' => 'brand_owner',
            ]
        );

        $this->command?->info('✅ BrandOwnerSeeder: User brand owner berhasil dibuat.');
    }
}
