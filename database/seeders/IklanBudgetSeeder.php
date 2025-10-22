<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IklanBudget;
use App\Models\Brand;
use Carbon\Carbon;

class IklanBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure required tables exist
        if (!DB::getSchemaBuilder()->hasTable('iklan_budgets')) {
            $this->command->warn('iklan_budgets table does not exist. Skipping IklanBudgetSeeder.');
            return;
        }
        if (!DB::getSchemaBuilder()->hasTable('brands')) {
            $this->command->warn('brands table does not exist. Skipping IklanBudgetSeeder.');
            return;
        }

        $brands = Brand::select('id', 'nama')->get();
        if ($brands->isEmpty()) {
            $this->command->warn('No brands found. Please run BrandSeeder first.');
            return;
        }

        // Seed data for the last 45 days
        $days = 45;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->startOfDay();

        $createdCount = 0;
        $current = $startDate->copy();

        $this->command->info("Seeding Iklan Budget data from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} for ".$brands->count()." brands...");

        while ($current->lte($endDate)) {
            foreach ($brands as $brand) {
                // Avoid duplicates per brand per date
                $exists = IklanBudget::where('tanggal', $current->format('Y-m-d'))
                    ->where('brand_id', $brand->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Realistic daily patterns: lower spend on weekends
                $weekdayFactor = $current->isWeekend() ? 0.6 : 1.0;

                // Baseline per brand to vary spend
                $brandBaseline = 400000 + (crc32((string)$brand->id) % 600000); // 400k - 1m baseline

                // Spent amount with some noise
                $spent = (int) round(($brandBaseline + rand(25000, 125000)) * $weekdayFactor);
                if ($spent < 150000) {
                    $spent = 150000; // minimum spend threshold
                }

                // Budget amount slightly above spent (10% - 30%)
                $budgetAmount = (int) round($spent * (1 + (rand(10, 30) / 100)));

                // Create record (fillable fields only)
                $budget = IklanBudget::create([
                    'tanggal' => $current->format('Y-m-d'),
                    'brand_id' => $brand->id,
                    'spent_amount' => $spent,
                ]);

                // Set non-fillable fields explicitly then save
                $budget->budget_amount = $budgetAmount;
                $budget->keterangan = 'Seeded data';
                $budget->save();

                $createdCount++;
            }

            $current->addDay();
        }

        // Optionally update closing and omset values based on existing transactions
        try {
            $updated = IklanBudget::updateClosingAndOmsetForPeriod($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            $this->command->info("Updated closing and omset for {$updated} budget records.");
        } catch (\Throwable $e) {
            // If transaksis table or data not ready, skip silently
            $this->command->warn('Skipping closing/omset update (missing data or tables).');
        }

        $this->command->info("✅ IklanBudgetSeeder completed. Created {$createdCount} new records.");
    }
}