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
            [
                'nama' => 'Hot Prospect', 
                'warna' => '#EF4444', // Red
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Warm Lead', 
                'warna' => '#F59E0B', // Amber
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Cold Lead', 
                'warna' => '#3B82F6', // Blue
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Qualified', 
                'warna' => '#10B981', // Green
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Nurturing', 
                'warna' => '#8B5CF6', // Purple
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Opportunity', 
                'warna' => '#EC4899', // Pink
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Proposal Sent', 
                'warna' => '#06B6D4', // Cyan
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Closing', 
                'warna' => '#84CC16', // Lime
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Won', 
                'warna' => '#059669', // Emerald
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Lost', 
                'warna' => '#6B7280', // Gray
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'No Response', 
                'warna' => '#9CA3AF', // Gray 400
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($labels as $label) {
            Label::firstOrCreate(
                ['nama' => $label['nama']], 
                $label
            );
        }
    }
}
