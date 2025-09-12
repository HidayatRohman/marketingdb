<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@marketingdb.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        // Buat additional super admin untuk testing jika belum ada
        $existingSuperAdmins = User::where('role', 'super_admin')->count();
        if ($existingSuperAdmins < 3) {
            $needToCreate = 3 - $existingSuperAdmins;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'super_admin',
                ]);
        }

        $this->command->info('Super Admin seeder completed successfully!');
    }
}
