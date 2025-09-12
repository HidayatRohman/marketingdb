<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use App\Models\User;
use Carbon\Carbon;

class MitraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada brand, label, dan user yang tersedia
        $brandIds = Brand::pluck('id')->toArray();
        $labelIds = Label::pluck('id')->toArray();
        $marketingUserIds = User::where('role', 'marketing')->pluck('id')->toArray();
        
        if (empty($brandIds)) {
            $this->command->error('No brands found. Please run BrandSeeder first.');
            return;
        }

        // Data Indonesia untuk realistis
        $cities = [
            ['kota' => 'Jakarta Selatan', 'provinsi' => 'DKI Jakarta'],
            ['kota' => 'Jakarta Utara', 'provinsi' => 'DKI Jakarta'], 
            ['kota' => 'Surabaya', 'provinsi' => 'Jawa Timur'],
            ['kota' => 'Bandung', 'provinsi' => 'Jawa Barat'],
            ['kota' => 'Medan', 'provinsi' => 'Sumatera Utara'],
            ['kota' => 'Bekasi', 'provinsi' => 'Jawa Barat'],
            ['kota' => 'Depok', 'provinsi' => 'Jawa Barat'],
            ['kota' => 'Tangerang', 'provinsi' => 'Banten'],
            ['kota' => 'Palembang', 'provinsi' => 'Sumatera Selatan'],
            ['kota' => 'Semarang', 'provinsi' => 'Jawa Tengah'],
            ['kota' => 'Makassar', 'provinsi' => 'Sulawesi Selatan'],
            ['kota' => 'Batam', 'provinsi' => 'Kepulauan Riau'],
            ['kota' => 'Pekanbaru', 'provinsi' => 'Riau'],
            ['kota' => 'Bandar Lampung', 'provinsi' => 'Lampung'],
            ['kota' => 'Malang', 'provinsi' => 'Jawa Timur'],
        ];

        $companyNames = [
            'PT Mitra Sejahtera', 'CV Berkah Jaya', 'Toko Makmur', 'PT Digital Solution',
            'CV Teknologi Maju', 'PT Solusi Bisnis', 'Toko Elektronik Prima', 'CV Mandiri Jaya',
            'PT Inovasi Teknologi', 'Toko Fashion Modern', 'CV Berkah Teknologi', 'PT Sukses Mandiri',
            'Toko Komputer Jaya', 'CV Digital Prima', 'PT Maju Bersama', 'Toko Online Center',
            'CV Teknologi Terpadu', 'PT Bisnis Modern', 'Toko Gadget Pro', 'CV Solusi Digital',
            'PT Kreatif Media', 'Toko Aksesoris Lengkap', 'CV Inovasi Muda', 'PT Dinamis Teknologi',
            'Toko Peralatan Kantor', 'CV Sukses Teknologi', 'PT Media Kreatif', 'Toko Software House',
            'CV Generasi Digital', 'PT Solusi Kreatif', 'Toko Aplikasi Mobile', 'CV Teknologi Canggih',
            'PT Digital Inovation', 'Toko E-Commerce', 'CV Mitra Teknologi', 'PT Bisnis Online',
            'Toko Web Development', 'CV Digital Marketing', 'PT Startup Teknologi', 'Toko IT Solutions',
        ];

        $chatStatuses = ['masuk', 'followup', 'followup_2', 'followup_3'];

        $comments = [
            'Prospek bagus untuk development aplikasi mobile',
            'Tertarik dengan solusi e-commerce platform',
            'Membutuhkan sistem manajemen inventory',
            'Ingin upgrade website corporate',
            'Potensi kerjasama digital marketing',
            'Butuh solusi POS untuk retail',
            'Tertarik dengan cloud hosting solution',
            'Memerlukan sistem ERP terintegrasi',
            'Potensi project web development',
            'Ingin konsultasi digital transformation',
            'Butuh mobile app untuk bisnis',
            'Tertarik dengan social media management',
            'Memerlukan sistem CRM yang handal',
            'Potensi kerjasama jangka panjang',
            'Ingin solusi omnichannel marketing',
            'Butuh sistem booking online',
            'Tertarik dengan AI automation',
            'Memerlukan data analytics solution',
            'Potensi upgrade infrastruktur IT',
            'Ingin implementasi IoT solution',
        ];

        // Generate data untuk beberapa hari terakhir dengan distribusi jam yang realistis
        $mitras = [];
        $mitraCount = 0;

        // Generate data untuk 30 hari terakhir
        for ($day = 30; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);
            
            // Tentukan jumlah lead per hari (lebih banyak di hari kerja)
            $isWeekend = $date->isWeekend();
            $baseLeads = $isWeekend ? rand(2, 8) : rand(5, 15);
            
            for ($i = 0; $i < $baseLeads; $i++) {
                $city = $cities[array_rand($cities)];
                $phoneNumber = '08' . rand(10, 99) . rand(10000000, 99999999);
                
                // Distribusi jam yang realistis (lebih banyak di jam kerja)
                if ($isWeekend) {
                    $hour = rand(10, 20); // Weekend: 10:00 - 20:00
                } else {
                    // Weekday: puncak di jam 9-11, 13-15, 19-21
                    $hourDistribution = [9, 9, 10, 10, 10, 11, 11, 13, 13, 14, 14, 15, 19, 19, 20, 21];
                    $hour = $hourDistribution[array_rand($hourDistribution)];
                }
                
                $createdAt = $date->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(rand(0, 59));
                
                $mitras[] = [
                    'nama' => $companyNames[$mitraCount % count($companyNames)],
                    'no_telp' => $phoneNumber,
                    'brand_id' => $brandIds[array_rand($brandIds)],
                    'label_id' => !empty($labelIds) ? $labelIds[array_rand($labelIds)] : null,
                    'user_id' => !empty($marketingUserIds) ? $marketingUserIds[array_rand($marketingUserIds)] : null,
                    'chat' => $chatStatuses[array_rand($chatStatuses)],
                    'kota' => $city['kota'],
                    'provinsi' => $city['provinsi'],
                    'komentar' => $comments[array_rand($comments)],
                    'tanggal_lead' => $date->format('Y-m-d'),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                
                $mitraCount++;
            }
        }

        // Insert data secara batch untuk performa
        $chunks = array_chunk($mitras, 50);
        foreach ($chunks as $chunk) {
            Mitra::insert($chunk);
        }

        $this->command->info("Mitra seeder completed successfully! Generated {$mitraCount} mitra records.");
    }
}
