<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\MitraController;
use Illuminate\Http\Request;

// Test basic export functionality
echo "Testing Export/Import Mitra functionality...\n";

// Check if PhpSpreadsheet is properly installed
if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo "✓ PhpSpreadsheet is installed\n";
} else {
    echo "✗ PhpSpreadsheet is NOT installed\n";
}

// Check if controller exists
if (class_exists('App\Http\Controllers\MitraController')) {
    echo "✓ MitraController exists\n";
} else {
    echo "✗ MitraController NOT found\n";
}

// Check if export method exists
if (method_exists(MitraController::class, 'export')) {
    echo "✓ export method exists\n";
} else {
    echo "✗ export method NOT found\n";
}

// Check if import method exists
if (method_exists(MitraController::class, 'import')) {
    echo "✓ import method exists\n";
} else {
    echo "✗ import method NOT found\n";
}

// Check if downloadTemplate method exists
if (method_exists(MitraController::class, 'downloadTemplate')) {
    echo "✓ downloadTemplate method exists\n";
} else {
    echo "✗ downloadTemplate method NOT found\n";
}

echo "\nExport/Import functionality ready for testing!\n";
echo "\nFeatures added:\n";
echo "- Export data to CSV/XLSX\n";
echo "- Import data from CSV/XLSX\n";
echo "- Download template files\n";
echo "- Role-based access control\n";
echo "- Data validation during import\n";
echo "- Error handling and user feedback\n";
