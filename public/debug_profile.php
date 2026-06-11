<?php
require_once dirname(__DIR__) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;

session();

header('Content-Type: text/plain');

if (!Session::getInstance()->has('user')) {
    echo "Not logged in\n";
    exit;
}

$user = Session::getInstance()->get('user');
echo "User ID: " . ($user->id ?? 'MISSING') . "\n\n";

// Test User model
try {
    $userModel = new \App\Models\User();
    $profile = $userModel->getById($user->id);
    if ($profile) {
        echo "User::getById: OK - found user: {$profile->first_name} {$profile->last_name}\n";
    } else {
        echo "User::getById: returned NULL\n";
    }
} catch (\Throwable $e) {
    echo "User::getById ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

// Test user_preferences table
try {
    $db = Database::getInstance();
    $prefs = $db->fetch("SELECT preferences FROM user_preferences WHERE user_id = ?", [$user->id]);
    if ($prefs) {
        echo "user_preferences: OK - found\n";
    } else {
        echo "user_preferences: no row found (not an error)\n";
    }
} catch (\Throwable $e) {
    echo "user_preferences ERROR: " . $e->getMessage() . "\n";
}

// List all tables
try {
    $tables = $db->fetchAll("SHOW TABLES");
    echo "\nTables in database:\n";
    foreach ($tables as $t) {
        $row = (array)$t;
        echo "  - " . reset($row) . "\n";
    }
} catch (\Throwable $e) {
    echo "SHOW TABLES ERROR: " . $e->getMessage() . "\n";
}
