<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Test login credentials
$users = [
    'superadmin@marketingdb.com',
    'admin@marketingdb.com', 
    'marketing@marketingdb.com'
];

echo "Testing user credentials:\n";
echo "========================\n";

foreach ($users as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $passwordCheck = Hash::check('password', $user->password);
        echo "Email: {$user->email}\n";
        echo "Name: {$user->name}\n";
        echo "Role: {$user->role}\n";
        echo "Password 'password': " . ($passwordCheck ? 'VALID' : 'INVALID') . "\n";
        echo "-------------------------\n";
    } else {
        echo "User with email {$email} not found\n";
        echo "-------------------------\n";
    }
}

echo "\nAll users in database:\n";
echo "======================\n";
User::all(['id', 'name', 'email', 'role'])->each(function($user) {
    echo "ID: {$user->id} | {$user->name} ({$user->email}) | Role: {$user->role}\n";
});
