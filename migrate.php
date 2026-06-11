<?php
/**
 * Run database migrations on Railway deploy.
 * Usage: php migrate.php
 */

require_once __DIR__ . '/app/helpers/functions.php';

// Connect using MYSQL_URL
$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
if (!$dbUrl) {
    echo "No MYSQL_URL or DATABASE_URL environment variable found.\n";
    exit(1);
}

$parsed = parse_url($dbUrl);
$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? '3306';
$dbname = ltrim($parsed['path'] ?? 'task_management', '/');
$username = $parsed['user'] ?? 'root';
$password = $parsed['pass'] ?? '';

$mysqli = new mysqli($host, $username, $password, $dbname, (int)$port);
if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
echo "Connected to MySQL ($host:$port/$dbname)\n";

$sqlFiles = [
    __DIR__ . '/database/schema.sql',
    __DIR__ . '/database/migration_task_upgrade.sql',
];

foreach ($sqlFiles as $file) {
    if (!file_exists($file)) {
        echo "Skip (not found): $file\n";
        continue;
    }

    $sql = file_get_contents($file);
    if (empty(trim($sql))) continue;

    // Remove CREATE DATABASE and USE statements (Railway provides the DB)
    $sql = preg_replace('/^CREATE\s+DATABASE.*?;/ims', '', $sql);
    $sql = preg_replace('/^USE\s+`?[^;]+`?;/ims', '', $sql);
    $sql = trim($sql);

    if ($mysqli->multi_query($sql)) {
        $count = 0;
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            $count++;
        } while ($mysqli->next_result());
        echo "Executed SQL from " . basename($file) . "\n";
    } else {
        echo "Error in " . basename($file) . ": " . $mysqli->error . "\n";
    }
}

$mysqli->close();
echo "Migration complete.\n";
