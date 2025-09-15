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
        User::firstOrCreate(
            ['email' => 'admin@marketingdb.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Tambahkan admin untuk testing jika belum ada
        $existingAdmins = User::where('role', 'admin')->count();
        if ($existingAdmins < 4) { // 1 admin utama + 3 testing
            $needToCreate = 4 - $existingAdmins;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'admin',
                ]);
        }

        // Super Admin utama
        User::firstOrCreate(
            ['email' => 'superadmin@marketingdb.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        // Tambahkan super admin untuk testing jika belum ada
        $existingSuperAdmins = User::where('role', 'super_admin')->count();
        if ($existingSuperAdmins < 3) { // 1 super admin utama + 2 testing
            $needToCreate = 3 - $existingSuperAdmins;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'super_admin',
                ]);
        }

        // Marketing utama
        User::firstOrCreate(
            ['email' => 'marketing@marketingdb.com'],
            [
                'name' => 'Marketing User',
                'password' => Hash::make('password'),
                'role' => 'marketing',
                'email_verified_at' => now(),
            ]
        );

        // Tambahkan marketing untuk testing jika belum ada
        $existingMarketing = User::where('role', 'marketing')->count();
        if ($existingMarketing < 5) { // 1 marketing utama + 4 testing
            $needToCreate = 5 - $existingMarketing;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'marketing',
                ]);
        }

        $this->command->info('Admin, Super Admin, and Marketing seeder completed successfully!');
    }
}
