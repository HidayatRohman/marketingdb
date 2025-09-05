<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Brand;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mitra>
 */
class MitraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->company(),
            'no_telp' => fake()->phoneNumber(),
            'brand_id' => Brand::factory(),
            'chat' => fake()->randomElement(['masuk', 'followup']),
            'kota' => fake()->city(),
            'provinsi' => fake()->state(),
            'transaksi' => fake()->randomFloat(2, 100000, 10000000),
            'komentar' => fake()->paragraph(),
        ];
    }
}
