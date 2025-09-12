<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat beberapa marketing user dengan data yang realistis
        $marketingUsers = [
            [
                'name' => 'Marketing Lead',
                'email' => 'marketing@marketingdb.com',
                'password' => Hash::make('password'),
                'role' => 'marketing',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Digital Marketing',
                'email' => 'digital@marketingdb.com',
                'password' => Hash::make('password'),
                'role' => 'marketing',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Social Media Marketing',
                'email' => 'social@marketingdb.com',
                'password' => Hash::make('password'),
                'role' => 'marketing',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($marketingUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Tambahkan marketing user tambahan dengan factory jika belum ada
        $existingMarketing = User::where('role', 'marketing')->count();
        $targetTotal = 8; // 3 predefined + 5 factory
        
        if ($existingMarketing < $targetTotal) {
            $needToCreate = $targetTotal - $existingMarketing;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'marketing',
                ]);
        }

        $this->command->info('Marketing users seeded successfully!');
    }
}
