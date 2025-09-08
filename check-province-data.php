<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Province Data ===\n";

// Check total mitra count
$totalMitra = \App\Models\Mitra::count();
echo "Total Mitra: {$totalMitra}\n\n";

// Check province data
echo "=== Province Data ===\n";
$mitras = \App\Models\Mitra::select('id', 'nama', 'provinsi', 'brand_id')->get();
foreach ($mitras as $mitra) {
    echo "ID: {$mitra->id}, Nama: {$mitra->nama}, Provinsi: '{$mitra->provinsi}', Brand ID: {$mitra->brand_id}\n";
}

echo "\n=== Unique Provinces ===\n";
$provinces = \App\Models\Mitra::select('provinsi')
    ->distinct()
    ->whereNotNull('provinsi')
    ->where('provinsi', '!=', '')
    ->where('provinsi', '!=', 'Unknown')
    ->get();

foreach ($provinces as $province) {
    echo "Province: '{$province->provinsi}'\n";
}

echo "\n=== Province Analytics (No Brand Filter) ===\n";
$provinceData = \App\Models\Mitra::select('provinsi', \DB::raw('COUNT(*) as total'))
    ->whereNotNull('provinsi')
    ->where('provinsi', '!=', '')
    ->where('provinsi', '!=', 'Unknown')
    ->groupBy('provinsi')
    ->orderByDesc('total')
    ->limit(7)
    ->get();

foreach ($provinceData as $data) {
    echo "Province: '{$data->provinsi}', Count: {$data->total}\n";
}

echo "\n=== Available Brands ===\n";
$brands = \App\Models\Brand::select('id', 'nama')->get();
foreach ($brands as $brand) {
    echo "Brand ID: {$brand->id}, Name: {$brand->nama}\n";
}

echo "\n=== Testing Brand Filter ===\n";
if ($brands->count() > 0) {
    $firstBrand = $brands->first();
    echo "Testing with Brand ID: {$firstBrand->id} ({$firstBrand->nama})\n";
    
    $provinceDataWithBrand = \App\Models\Mitra::select('provinsi', \DB::raw('COUNT(*) as total'))
        ->where('brand_id', $firstBrand->id)
        ->whereNotNull('provinsi')
        ->where('provinsi', '!=', '')
        ->where('provinsi', '!=', 'Unknown')
        ->groupBy('provinsi')
        ->orderByDesc('total')
        ->limit(7)
        ->get();
    
    foreach ($provinceDataWithBrand as $data) {
        echo "Province: '{$data->provinsi}', Count: {$data->total}\n";
    }
}
