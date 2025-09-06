<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive dashboard data...');
        
        // Create more diverse date range data
        $this->createTimeBasedMitras();
        
        // Update existing mitras with realistic timeline
        $this->updateExistingMitras();
        
        $this->command->info('Dashboard data seeding completed!');
    }
    
    private function createTimeBasedMitras()
    {
        $marketingUsers = User::where('role', 'marketing')->get();
        $brands = Brand::all();
        $labels = Label::all();
        
        if ($marketingUsers->isEmpty() || $brands->isEmpty() || $labels->isEmpty()) {
            $this->command->error('Missing required data. Please run other seeders first.');
            return;
        }
        
        // Create leads for the last 30 days with realistic patterns
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // More leads on weekdays, fewer on weekends
            $leadsCount = $date->isWeekend() ? rand(0, 2) : rand(1, 5);
            
            for ($j = 0; $j < $leadsCount; $j++) {
                $this->createMitra($date, $marketingUsers, $brands, $labels);
            }
        }
    }
    
    private function createMitra($date, $marketingUsers, $brands, $labels)
    {
        $companies = [
            'PT Teknologi Maju', 'CV Digital Prima', 'Toko Online Sukses',
            'PT Global Solutions', 'CV Inovasi Bisnis', 'Toko Elektronik Modern',
            'PT Retail Terpadu', 'CV Marketing Digital', 'Toko Fashion Online',
            'PT Logistik Cepat', 'CV Kreatif Media', 'Toko Makanan Sehat',
            'PT Properti Indah', 'CV Konsultan Bisnis', 'Toko Gadget Murah',
            'PT Manufaktur Hebat', 'CV Startup Inovatif', 'Toko Buku Online',
            'PT Finansial Aman', 'CV Pendidikan Modern'
        ];
        
        $cities = [
            ['Jakarta', 'DKI Jakarta'],
            ['Surabaya', 'Jawa Timur'],
            ['Bandung', 'Jawa Barat'],
            ['Medan', 'Sumatera Utara'],
            ['Semarang', 'Jawa Tengah'],
            ['Makassar', 'Sulawesi Selatan'],
            ['Palembang', 'Sumatera Selatan'],
            ['Yogyakarta', 'DI Yogyakarta'],
            ['Denpasar', 'Bali'],
            ['Balikpapan', 'Kalimantan Timur']
        ];
        
        $companyName = $companies[array_rand($companies)] . ' ' . rand(1, 99);
        $location = $cities[array_rand($cities)];
        
        // Realistic progression: masuk -> followup over time
        $daysSinceCreated = Carbon::now()->diffInDays($date);
        $chatStatus = 'masuk';
        
        // Older leads more likely to be in followup
        if ($daysSinceCreated > 7 && rand(1, 100) <= 60) {
            $chatStatus = 'followup';
        } elseif ($daysSinceCreated > 3 && rand(1, 100) <= 30) {
            $chatStatus = 'followup';
        }
        
        Mitra::create([
            'nama' => $companyName,
            'no_telp' => '08' . rand(10000000, 99999999),
            'tanggal_lead' => $date->format('Y-m-d'),
            'user_id' => $marketingUsers->random()->id,
            'brand_id' => $brands->random()->id,
            'label_id' => $labels->random()->id,
            'chat' => $chatStatus,
            'kota' => $location[0],
            'provinsi' => $location[1],
            'komentar' => $this->generateComment($chatStatus, $daysSinceCreated),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
    
    private function updateExistingMitras()
    {
        $mitras = Mitra::whereDate('created_at', '>', Carbon::now()->subDays(30))->get();
        
        foreach ($mitras as $mitra) {
            $daysSinceCreated = Carbon::now()->diffInDays($mitra->created_at);
            
            // Update chat status based on age
            if ($daysSinceCreated > 10 && $mitra->chat === 'masuk' && rand(1, 100) <= 70) {
                $mitra->update([
                    'chat' => 'followup',
                    'komentar' => $this->generateComment('followup', $daysSinceCreated)
                ]);
            }
        }
    }
    
    private function generateComment($chatStatus, $daysSince)
    {
        $masukComments = [
            'Lead baru dari website',
            'Tertarik dengan produk premium',
            'Menanyakan harga dan detail',
            'Prospek yang sangat potensial',
            'Membutuhkan informasi lebih lanjut'
        ];
        
        $followupComments = [
            'Sudah follow up via WhatsApp',
            'Akan meeting minggu depan',
            'Menunggu approval dari atasan',
            'Tertarik untuk kerjasama jangka panjang',
            'Meminta proposal detail'
        ];
        
        if ($chatStatus === 'followup') {
            return $followupComments[array_rand($followupComments)] . " (Follow up ke-{$daysSince})";
        }
        
        return $masukComments[array_rand($masukComments)];
    }
}
