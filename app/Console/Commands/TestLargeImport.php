<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestLargeImport extends Command
{
    protected $signature = 'test:large-import';
    protected $description = 'Test processing large data similar to Excel import';

    public function handle()
    {
        $this->info('Testing large import simulation...');

        try {
            // Simulate reading large Excel file
            $totalRows = 1000; // Adjust based on your actual file size
            $batchSize = 100;
            $processed = 0;
            $errors = 0;

            DB::beginTransaction();

            for ($i = 1; $i <= $totalRows; $i++) {
                try {
                    // Simulate row processing similar to import
                    $tanggal = '2025-10-' . str_pad((string)(($i % 28) + 1), 2, '0', STR_PAD_LEFT);
                    $brandId = ($i % 8) + 1; // Assuming 8 brands
                    $spentAmount = rand(50000, 500000);

                    // Check if exists (upsert logic)
                    $exists = DB::table('iklan_budgets')
                        ->where('tanggal', $tanggal)
                        ->where('brand_id', $brandId)
                        ->exists();

                    if ($exists) {
                        // Update
                        DB::table('iklan_budgets')
                            ->where('tanggal', $tanggal)
                            ->where('brand_id', $brandId)
                            ->update(['spent_amount' => $spentAmount]);
                    } else {
                        // Insert
                        DB::table('iklan_budgets')->insert([
                            'tanggal' => $tanggal,
                            'brand_id' => $brandId,
                            'spent_amount' => $spentAmount,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    $processed++;

                    // Batch processing
                    if ($processed % $batchSize == 0) {
                        $this->info("Processed {$processed} rows...");
                        DB::commit();
                        DB::beginTransaction();
                    }

                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error processing row {$i}: " . $e->getMessage());
                }
            }

            DB::commit();

            $this->info("Import simulation completed!");
            $this->info("Total processed: {$processed}");
            $this->info("Errors: {$errors}");
            $this->info("Memory used: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import simulation failed: " . $e->getMessage());
            Log::error("Large import test failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }

        return 0;
    }
}
