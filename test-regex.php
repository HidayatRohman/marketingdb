<?php

$patterns = [
    '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
    'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
];

$testColors = [
    '#6B7280',
    '#ff0000',
    '#FFF',
    'invalid',
];

foreach ($patterns as $pattern) {
    echo "Testing pattern: $pattern\n";
    
    foreach ($testColors as $color) {
        if (strpos($pattern, 'regex:') === 0) {
            $actualPattern = substr($pattern, 6); // Remove 'regex:' prefix
        } else {
            $actualPattern = $pattern;
        }
        
        echo "  Color: $color -> ";
        if (preg_match($actualPattern, $color)) {
            echo "MATCH\n";
        } else {
            echo "NO MATCH\n";
        }
    }
    echo "\n";
}
