<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;

echo "=== Creating More Sample Data ===\n\n";

$marketingUsers = User::where('role', 'marketing')->get();
$brands = Brand::all();
$labels = Label::all();

// Create more sample mitras
$sampleMitras = [
    ['nama' => 'PT Digital Solutions', 'no_telp' => '081234567899', 'chat' => 'masuk', 'kota' => 'Jakarta', 'provinsi' => 'DKI Jakarta'],
    ['nama' => 'CV Tech Innovate', 'no_telp' => '081234567898', 'chat' => 'followup', 'kota' => 'Bandung', 'provinsi' => 'Jawa Barat'],
    ['nama' => 'Toko Online Sejahtera', 'no_telp' => '081234567897', 'chat' => 'masuk', 'kota' => 'Surabaya', 'provinsi' => 'Jawa Timur'],
    ['nama' => 'PT Maju Bersama', 'no_telp' => '081234567896', 'chat' => 'followup', 'kota' => 'Medan', 'provinsi' => 'Sumatera Utara'],
    ['nama' => 'CV Sukses Mandiri', 'no_telp' => '081234567895', 'chat' => 'masuk', 'kota' => 'Yogyakarta', 'provinsi' => 'DI Yogyakarta'],
    ['nama' => 'Toko Berkah Online', 'no_telp' => '081234567894', 'chat' => 'followup', 'kota' => 'Semarang', 'provinsi' => 'Jawa Tengah'],
    ['nama' => 'PT Global Trading', 'no_telp' => '081234567893', 'chat' => 'masuk', 'kota' => 'Makassar', 'provinsi' => 'Sulawesi Selatan'],
    ['nama' => 'CV Prima Jaya', 'no_telp' => '081234567892', 'chat' => 'followup', 'kota' => 'Denpasar', 'provinsi' => 'Bali'],
    ['nama' => 'Toko Elektronik Maju', 'no_telp' => '081234567891', 'chat' => 'masuk', 'kota' => 'Palembang', 'provinsi' => 'Sumatera Selatan'],
    ['nama' => 'PT Indonesia Hebat', 'no_telp' => '081234567890', 'chat' => 'followup', 'kota' => 'Balikpapan', 'provinsi' => 'Kalimantan Timur'],
];

echo "Creating " . count($sampleMitras) . " sample mitras...\n\n";

foreach ($sampleMitras as $mitraData) {
    // Add random assignments
    $mitraData['user_id'] = $marketingUsers->random()->id;
    $mitraData['brand_id'] = $brands->random()->id;
    $mitraData['label_id'] = $labels->random()->id;
    $mitraData['tanggal_lead'] = now()->subDays(rand(0, 30))->format('Y-m-d');
    $mitraData['komentar'] = 'Sample data untuk testing dashboard analytics';
    
    $mitra = Mitra::create($mitraData);
    echo "Created: {$mitra->nama} - {$mitra->chat} - {$mitra->tanggal_lead}\n";
}

echo "\n=== Final Statistics ===\n";

$totalMitras = Mitra::count();
$masukCount = Mitra::where('chat', 'masuk')->count();
$followupCount = Mitra::where('chat', 'followup')->count();
$todayCount = Mitra::whereDate('tanggal_lead', today())->count();

echo "Total Mitras: {$totalMitras}\n";
echo "Chat Masuk: {$masukCount}\n";
echo "Follow Up: {$followupCount}\n";
echo "Today's Leads: {$todayCount}\n";

echo "\nMarketing Performance:\n";
$marketingStats = User::where('role', 'marketing')
    ->withCount([
        'mitras as total_leads',
        'mitras as closed_leads' => function ($query) {
            $query->where('chat', 'followup');
        }
    ])
    ->having('total_leads', '>', 0)
    ->get();

foreach ($marketingStats as $user) {
    $rate = $user->total_leads > 0 ? round(($user->closed_leads / $user->total_leads) * 100, 1) : 0;
    echo "- {$user->name}: {$user->total_leads} leads, {$user->closed_leads} closed ({$rate}%)\n";
}

echo "\nLabel Distribution:\n";
$labelStats = Label::withCount('mitras')->get();
foreach ($labelStats as $label) {
    $percentage = $totalMitras > 0 ? round(($label->mitras_count / $totalMitras) * 100, 1) : 0;
    echo "- {$label->nama}: {$label->mitras_count} ({$percentage}%)\n";
}

echo "\n=== Data Creation Complete ===\n";
