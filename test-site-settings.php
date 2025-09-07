<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SiteSetting;

echo "Testing Site Settings:\n";
echo "=====================\n";

try {
    // Test getting site title
    $siteTitle = SiteSetting::get('site_title', 'Default Title');
    echo "Site Title: " . $siteTitle . "\n";
    
    // Test getting site description
    $siteDescription = SiteSetting::get('site_description', 'Default Description');
    echo "Site Description: " . $siteDescription . "\n";
    
    // Test getting logo
    $siteLogo = SiteSetting::get('site_logo');
    echo "Site Logo: " . ($siteLogo ? $siteLogo : 'No logo set') . "\n";
    
    // Test getting favicon
    $siteFavicon = SiteSetting::get('site_favicon');
    echo "Site Favicon: " . ($siteFavicon ? $siteFavicon : 'No favicon set') . "\n";
    
    echo "\n✅ Site Settings are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
