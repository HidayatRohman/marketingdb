<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdvertiserUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Advertiser Test',
            'email' => 'advertiser.test.' . time() . '@marketingdb.com',
            'password' => Hash::make('password'),
            'role' => 'advertiser',
        ]);
    }
}