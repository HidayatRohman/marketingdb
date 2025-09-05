<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleBasedUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeder ini menggunakan factory methods untuk membuat users dengan role tertentu
     */
    public function run(): void
    {
        // Menggunakan factory methods untuk membuat users dengan role tertentu
        
        // Super Admins menggunakan factory method
        User::factory()
            ->superAdmin()
            ->count(2)
            ->create();

        // Admins menggunakan factory method
        User::factory()
            ->admin()
            ->count(3)
            ->create();

        // Marketing users menggunakan factory method
        User::factory()
            ->marketing()
            ->count(8)
            ->create();

        // Contoh membuat user dengan state tertentu
        User::factory()
            ->superAdmin()
            ->unverified()
            ->create([
                'name' => 'Unverified Super Admin',
                'email' => 'unverified.superadmin@marketingdb.com',
            ]);
    }
}
