<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin utama
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@marketingdb.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@marketingdb.com',
            'password' => Hash::make('password'),
            'role' => 'superAdmin',
            'email_verified_at' => now(),
        ]);

        // Tambahkan 3 admin untuk testing
        User::factory()
            ->count(3)
            ->create([
                'role' => 'admin',
            ]);

        // Tambahkan 5 marketing untuk testing
        User::factory()
            ->count(5)
            ->create([
                'role' => 'marketing',
            ]);
    }
}
