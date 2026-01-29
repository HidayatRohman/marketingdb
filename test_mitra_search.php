<?php

use App\Models\Mitra;

// Ensure we have a mitra to test with
$mitra = Mitra::first();
if (!$mitra) {
    echo "No mitras found to test.\n";
    exit;
}

echo "Testing with Mitra ID: {$mitra->id}, Name: {$mitra->nama}, Phone: {$mitra->no_telp}\n";

// Test 1: Exact match
$phone = $mitra->no_telp;
echo "Test 1: Exact match '{$phone}'... ";
$found = Mitra::where('no_telp', $phone)->first();
echo $found ? "FOUND\n" : "NOT FOUND\n";

// Test 2: Last 10 digits
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
$last10 = substr($cleanPhone, -10);
echo "Test 2: Last 10 digits '{$last10}'... ";

// Replicate Controller Logic
$driver = config('database.default');
$connection = config("database.connections.{$driver}.driver");
$query = Mitra::query();

if ($connection === 'sqlite') {
    $query->whereRaw("REPLACE(REPLACE(REPLACE(no_telp, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last10}"]);
} else {
    // MySQL/PostgreSQL
    $query->whereRaw("REGEXP_REPLACE(no_telp, '[^0-9]', '') LIKE ?", ["%{$last10}"]);
}
$found = $query->first();
echo $found ? "FOUND\n" : "NOT FOUND (Query Logic Issue)\n";


// Test 3: Simulate Controller Method Logic
$requestPhone = "0" . $last10; // Try with leading 0
echo "Test 3: Simulated Request '{$requestPhone}'... ";

$cleanRequest = preg_replace('/[^0-9]/', '', $requestPhone);
if (strlen($cleanRequest) > 10) {
    $searchPhone = substr($cleanRequest, -10);
} else {
    $searchPhone = $cleanRequest;
}

$q2 = Mitra::query();
if ($connection === 'sqlite') {
    $q2->whereRaw("REPLACE(REPLACE(REPLACE(no_telp, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$searchPhone}"]);
} else {
    $q2->whereRaw("REGEXP_REPLACE(no_telp, '[^0-9]', '') LIKE ?", ["%{$searchPhone}"]);
}
$found2 = $q2->first();
echo $found2 ? "FOUND\n" : "NOT FOUND\n";
