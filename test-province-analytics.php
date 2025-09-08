<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing BrandController getProvinceAnalytics Method ===\n";

// Create a mock user with admin privileges (assuming super admin doesn't have limited access)
$user = new class {
    public function hasLimitedAccess() {
        return false; // Simulating admin user
    }
    
    public function id() {
        return 1;
    }
};

// Test without brand filter
echo "\n=== Without Brand Filter ===\n";
$query = \App\Models\Mitra::query();

// Apply role-based filtering
if ($user->hasLimitedAccess()) {
    $query->where('user_id', $user->id);
}

// Get top 7 provinces by mitra count
$provinceData = $query->select('provinsi', \DB::raw('COUNT(*) as total'))
    ->whereNotNull('provinsi')
    ->where('provinsi', '!=', '')
    ->where('provinsi', '!=', 'Unknown')
    ->groupBy('provinsi')
    ->orderByDesc('total')
    ->limit(7)
    ->get();

$analytics = [
    'labels' => $provinceData->pluck('provinsi')->toArray(),
    'data' => $provinceData->pluck('total')->toArray(),
    'total' => $provinceData->sum('total'),
    'selected_brand' => 'Semua Brand',
];

echo "Labels: " . json_encode($analytics['labels']) . "\n";
echo "Data: " . json_encode($analytics['data']) . "\n";
echo "Total: " . $analytics['total'] . "\n";
echo "Selected Brand: " . $analytics['selected_brand'] . "\n";

// Test with brand filter
echo "\n=== With Brand Filter (Brand ID 1) ===\n";
$selectedBrandId = 1;
$query2 = \App\Models\Mitra::query();

// Apply role-based filtering
if ($user->hasLimitedAccess()) {
    $query2->where('user_id', $user->id);
}

// Filter by selected brand
$query2->where('brand_id', $selectedBrandId);

// Get top 7 provinces by mitra count
$provinceData2 = $query2->select('provinsi', \DB::raw('COUNT(*) as total'))
    ->whereNotNull('provinsi')
    ->where('provinsi', '!=', '')
    ->where('provinsi', '!=', 'Unknown')
    ->groupBy('provinsi')
    ->orderByDesc('total')
    ->limit(7)
    ->get();

$analytics2 = [
    'labels' => $provinceData2->pluck('provinsi')->toArray(),
    'data' => $provinceData2->pluck('total')->toArray(),
    'total' => $provinceData2->sum('total'),
    'selected_brand' => \App\Models\Brand::find($selectedBrandId)?->nama,
];

echo "Labels: " . json_encode($analytics2['labels']) . "\n";
echo "Data: " . json_encode($analytics2['data']) . "\n";
echo "Total: " . $analytics2['total'] . "\n";
echo "Selected Brand: " . $analytics2['selected_brand'] . "\n";
