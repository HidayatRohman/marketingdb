<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan seeder untuk setiap role
        $this->call([
            SuperAdminSeeder::class,
            AdminSeeder::class,
            MarketingSeeder::class,
            LabelSeeder::class,
            BrandSeeder::class,
            MitraSeeder::class,
        ]);

        // Buat test user tambahan jika diperlukan
        // User::factory(10)->create();
    }
}
