<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat user CS utama
        User::firstOrCreate(
            ['email' => 'cs@marketingdb.com'],
            [
                'name' => 'CS User',
                'password' => Hash::make('password'),
                'role' => 'cs',
                'email_verified_at' => now(),
            ]
        );

        // Tambahkan user CS tambahan dengan factory jika belum ada
        $existingCs = User::where('role', 'cs')->count();
        $targetTotal = 3; // 1 CS utama + 2 tambahan
        
        if ($existingCs < $targetTotal) {
            $needToCreate = $targetTotal - $existingCs;
            User::factory()
                ->count($needToCreate)
                ->create([
                    'role' => 'cs',
                ]);
        }

        $this->command->info('CS users seeded successfully!');
    }
}