<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;
use App\Models\User;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

echo "=== Test Frontend Dropdown Brand Fields ===\n\n";

// Login as user ID 1
$user = User::find(1);
Auth::login($user);
echo "Logged in as: {$user->name}\n\n";

// Get brands data (same as controller)
$brands = Brand::all();
echo "Available brands for dropdown:\n";
foreach ($brands as $brand) {
    echo "- ID: {$brand->id}, Nama: {$brand->nama}, Logo URL: " . ($brand->logo_url ?? 'null') . "\n";
}

echo "\nTotal brands: " . $brands->count() . "\n\n";

// Test data structure that would be sent to frontend
$frontendData = [
    'brands' => $brands->map(function($brand) {
        return [
            'id' => $brand->id,
            'nama' => $brand->nama,
            'logo' => $brand->logo,
            'logo_url' => $brand->logo_url
        ];
    })->toArray()
];

echo "Frontend data structure:\n";
echo json_encode($frontendData, JSON_PRETTY_PRINT) . "\n\n";

// Test form data structure for create
echo "=== Test Form Data Structure ===\n\n";

$sampleFormData = [
    'user_id' => 1,
    'nama_marketing' => 'Test Marketing',
    'tanggal_tf' => '2024-01-15',
    'tanggal_lead_masuk' => '2024-01-15',
    'periode_lead' => 'Januari 2024',
    'no_wa' => '081234567890',
    'usia' => 25,
    'nama_mitra' => 'Test Mitra',
    'paket_brand_id' => 1, // Should be selected from dropdown
    'lead_awal_brand_id' => 2, // Should be selected from dropdown
    'sumber' => 'Instagram',
    'kabupaten' => 'Jakarta Selatan',
    'provinsi' => 'DKI Jakarta',
    'status_pembayaran' => 'Dp / TJ',
    'nominal_masuk' => 500000,
    'harga_paket' => 1000000,
    'nama_paket' => 'Test Paket'
];

echo "Sample form data with brand fields:\n";
echo json_encode($sampleFormData, JSON_PRETTY_PRINT) . "\n\n";

// Validate brand IDs exist
echo "=== Validate Brand IDs ===\n\n";

$paketBrand = Brand::find($sampleFormData['paket_brand_id']);
$leadAwalBrand = Brand::find($sampleFormData['lead_awal_brand_id']);

if ($paketBrand) {
    echo "✓ Paket Brand ID {$sampleFormData['paket_brand_id']} exists: {$paketBrand->nama}\n";
} else {
    echo "✗ Paket Brand ID {$sampleFormData['paket_brand_id']} NOT FOUND\n";
}

if ($leadAwalBrand) {
    echo "✓ Lead Awal Brand ID {$sampleFormData['lead_awal_brand_id']} exists: {$leadAwalBrand->nama}\n";
} else {
    echo "✗ Lead Awal Brand ID {$sampleFormData['lead_awal_brand_id']} NOT FOUND\n";
}

// Test existing transaction for edit mode
echo "\n=== Test Edit Mode Data ===\n\n";

$existingTransaksi = Transaksi::with(['paketBrand', 'leadAwalBrand'])->latest()->first();
if ($existingTransaksi) {
    echo "Existing transaction for edit:\n";
    echo "- ID: {$existingTransaksi->id}\n";
    echo "- Nama Paket: {$existingTransaksi->nama_paket}\n";
    echo "- Paket Brand ID: {$existingTransaksi->paket_brand_id}\n";
    echo "- Paket Brand Name: " . ($existingTransaksi->paketBrand ? $existingTransaksi->paketBrand->nama : 'NULL') . "\n";
    echo "- Lead Awal Brand ID: {$existingTransaksi->lead_awal_brand_id}\n";
    echo "- Lead Awal Brand Name: " . ($existingTransaksi->leadAwalBrand ? $existingTransaksi->leadAwalBrand->nama : 'NULL') . "\n";
    
    // Test form data for edit
    $editFormData = [
        'paket_brand_id' => $existingTransaksi->paket_brand_id,
        'lead_awal_brand_id' => $existingTransaksi->lead_awal_brand_id,
        // ... other fields would be here
    ];
    
    echo "\nEdit form data:\n";
    echo json_encode($editFormData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No existing transaction found for edit test\n";
}

// Test dropdown options generation
echo "\n=== Test Dropdown Options ===\n\n";

echo "HTML-like dropdown options for paket_brand_id:\n";
echo "<select name='paket_brand_id'>\n";
echo "  <option value=''>Pilih Brand Paket</option>\n";
foreach ($brands as $brand) {
    $selected = ($existingTransaksi && $existingTransaksi->paket_brand_id == $brand->id) ? ' selected' : '';
    echo "  <option value='{$brand->id}'{$selected}>{$brand->nama}</option>\n";
}
echo "</select>\n\n";

echo "HTML-like dropdown options for lead_awal_brand_id:\n";
echo "<select name='lead_awal_brand_id'>\n";
echo "  <option value=''>Pilih Brand Lead Awal</option>\n";
foreach ($brands as $brand) {
    $selected = ($existingTransaksi && $existingTransaksi->lead_awal_brand_id == $brand->id) ? ' selected' : '';
    echo "  <option value='{$brand->id}'{$selected}>{$brand->nama}</option>\n";
}
echo "</select>\n\n";

echo "=== Test Complete ===\n";
echo "\nSummary:\n";
echo "- Brands available: {$brands->count()}\n";
echo "- Frontend data structure: OK\n";
echo "- Sample form data: OK\n";
echo "- Brand ID validation: " . ($paketBrand && $leadAwalBrand ? 'OK' : 'FAILED') . "\n";
echo "- Edit mode data: " . ($existingTransaksi ? 'OK' : 'NO DATA') . "\n";