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

        $this->command->info('Admin seeder completed successfully!');
    }
}
