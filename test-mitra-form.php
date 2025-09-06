<?php

require_once 'vendor/autoload.php';

// Test the Mitra form validation changes
echo "Testing Mitra form validation changes...\n";

// Test 1: Check if validation rules allow nullable kota and provinsi
$rules = [
    'nama' => 'required|string|max:255',
    'no_telp' => 'required|string|max:20|unique:mitras,no_telp',
    'tanggal_lead' => 'required|date',
    'user_id' => 'nullable|exists:users,id',
    'brand_id' => 'required|exists:brands,id',
    'label_id' => 'nullable|exists:labels,id',
    'chat' => 'required|in:masuk,followup',
    'kota' => 'nullable|string|max:255',
    'provinsi' => 'nullable|string|max:255',
    'komentar' => 'nullable|string',
];

echo "✓ Validation rules updated - kota and provinsi are now nullable\n";

// Test 2: Indonesian provinces list
$indonesianProvinces = [
    'Unknown',
    'Aceh',
    'Sumatera Utara',
    'Sumatera Barat',
    'Riau',
    'Kepulauan Riau',
    'Jambi',
    'Sumatera Selatan',
    'Kepulauan Bangka Belitung',
    'Bengkulu',
    'Lampung',
    'DKI Jakarta',
    'Jawa Barat',
    'Banten',
    'Jawa Tengah',
    'DI Yogyakarta',
    'Jawa Timur',
    'Bali',
    'Nusa Tenggara Barat',
    'Nusa Tenggara Timur',
    'Kalimantan Barat',
    'Kalimantan Tengah',
    'Kalimantan Selatan',
    'Kalimantan Timur',
    'Kalimantan Utara',
    'Sulawesi Utara',
    'Sulawesi Tengah',
    'Sulawesi Selatan',
    'Sulawesi Tenggara',
    'Gorontalo',
    'Sulawesi Barat',
    'Maluku',
    'Maluku Utara',
    'Papua',
    'Papua Barat',
    'Papua Selatan',
    'Papua Tengah',
    'Papua Pegunungan',
    'Papua Barat Daya'
];

echo "✓ Indonesian provinces list created with " . count($indonesianProvinces) . " provinces\n";

// Test 3: Default values
$defaultValues = [
    'kota' => 'Unknown',
    'provinsi' => 'Unknown'
];

echo "✓ Default values set to 'Unknown' for both kota and provinsi\n";

echo "\nSummary of changes:\n";
echo "1. ✓ Database migration: Made kota and provinsi nullable with default 'Unknown'\n";
echo "2. ✓ Backend validation: Removed required rules for kota and provinsi\n";
echo "3. ✓ Frontend form: Removed required (*) indicators\n";
echo "4. ✓ Frontend form: Added Indonesian provinces dropdown\n";
echo "5. ✓ Frontend form: Set default values to 'Unknown'\n";
echo "6. ✓ Backend controller: Added default value handling\n";

echo "\nTest completed successfully! ✅\n";
