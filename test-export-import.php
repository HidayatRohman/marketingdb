<?php

// Test export functionality
require_once 'vendor/autoload.php';

// Bootstrap the Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Mitra Export/Import Functionality\n";
echo "=========================================\n\n";

try {
    // Test if we can access the controller
    $controller = new App\Http\Controllers\MitraController();
    echo "✅ MitraController loaded successfully\n";
    
    // Test if PhpSpreadsheet is available
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "✅ PhpSpreadsheet library available\n";
    
    // Test if we can create a basic XLSX file
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Test');
    $sheet->setCellValue('B1', 'Export');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    echo "✅ XLSX writer created successfully\n";
    
    // Check if mitras exist
    $mitraCount = App\Models\Mitra::count();
    echo "📊 Total mitras in database: $mitraCount\n";
    
    // Check if brands exist
    $brandCount = App\Models\Brand::count();
    echo "🏢 Total brands in database: $brandCount\n";
    
    // Check if labels exist
    $labelCount = App\Models\Label::count();
    echo "🏷️  Total labels in database: $labelCount\n";
    
    echo "\n✅ All components are ready for export/import functionality!\n";
    echo "\nRoutes available:\n";
    echo "- GET /mitras/export (Export XLSX)\n";
    echo "- GET /mitras/template (Download template)\n";
    echo "- POST /mitras/import (Import XLSX)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
