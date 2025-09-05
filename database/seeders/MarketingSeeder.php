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
        User::create([
            'name' => 'Marketing User',
            'email' => 'marketing@marketingdb.com',
            'password' => Hash::make('password'),
            'role' => 'marketing',
            'email_verified_at' => now(),
        ]);

        // Buat additional marketing users untuk testing
        User::factory()
            ->count(5)
            ->create([
                'role' => 'marketing',
            ]);
    }
}
