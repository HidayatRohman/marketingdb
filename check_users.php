<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== SUPER ADMIN USERS ===\n";
$superAdmins = User::where('role', 'super_admin')->get(['name', 'email', 'role']);
foreach ($superAdmins as $user) {
    echo "- {$user->name} ({$user->email})\n";
}

echo "\n=== MARKETING USERS ===\n";
$marketingUsers = User::where('role', 'marketing')->get(['name', 'email', 'role']);
foreach ($marketingUsers as $user) {
    echo "- {$user->name} ({$user->email})\n";
}

echo "\n=== SUMMARY ===\n";
echo "Super Admin: " . $superAdmins->count() . " users\n";
echo "Marketing: " . $marketingUsers->count() . " users\n";
echo "Total: " . ($superAdmins->count() + $marketingUsers->count()) . " users\n";