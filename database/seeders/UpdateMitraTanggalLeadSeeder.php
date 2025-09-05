<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mitra;

class UpdateMitraTanggalLeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all mitras that don't have tanggal_lead set to today's date
        Mitra::whereNull('tanggal_lead')->update([
            'tanggal_lead' => now()->format('Y-m-d')
        ]);
        
        $this->command->info('Updated all mitras without tanggal_lead to today\'s date.');
    }
}
