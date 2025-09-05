<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Label;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            ['nama' => 'Greeting', 'warna' => '#10B981'], // Green
            ['nama' => 'Cold', 'warna' => '#3B82F6'], // Blue
            ['nama' => 'Hot Prospek', 'warna' => '#F59E0B'], // Amber
            ['nama' => 'Closing', 'warna' => '#EF4444'], // Red
            ['nama' => 'No Respon', 'warna' => '#6B7280'], // Gray
        ];

        foreach ($labels as $label) {
            Label::create($label);
        }
    }
}
