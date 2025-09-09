<?php

// Test export functionality
echo "Testing export functionality...\n";

// Simulate filters
$filters = [
    'periode_start' => '2025-09-01',
    'periode_end' => '2025-09-09',
];

$params = http_build_query($filters);
$url = "http://localhost:8000/mitras/export?" . $params;

echo "Export URL: " . $url . "\n";

// Test if we can reach the endpoint
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'User-Agent: PHP Test'
        ]
    ]
]);

echo "Testing endpoint availability...\n";

$headers = @get_headers($url, 1, $context);
if ($headers) {
    echo "Status: " . $headers[0] . "\n";
    
    if (strpos($headers[0], '200') !== false) {
        echo "✅ Export endpoint is responding correctly\n";
    } else {
        echo "❌ Export endpoint returned error\n";
    }
} else {
    echo "❌ Cannot reach export endpoint\n";
}

echo "\nTesting completed.\n";
