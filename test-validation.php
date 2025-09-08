<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the validation rules
$testData = [
    'nama' => 'Test Label',
    'warna' => '#6B7280'
];

$rules = [
    'nama' => 'required|string|max:255|unique:labels,nama',
    'warna' => [
        'required',
        'string',
        'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
    ]
];

$validator = Validator::make($testData, $rules);

if ($validator->fails()) {
    echo "Validation failed:\n";
    print_r($validator->errors()->toArray());
} else {
    echo "Validation passed successfully!\n";
    echo "Data: ";
    print_r($testData);
}
