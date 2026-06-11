<?php
/**
 * Run database migrations on Railway deploy.
 * Usage: php migrate.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/helpers/Database.php';
require_once __DIR__ . '/app/helpers/functions.php';

use App\Helpers\Database;

$sqlFiles = [
    __DIR__ . '/database/schema.sql',
    __DIR__ . '/database/migration_task_upgrade.sql',
];

$db = Database::getInstance()->getConnection();

foreach ($sqlFiles as $file) {
    if (!file_exists($file)) {
        echo "Skip (not found): $file\n";
        continue;
    }
    $sql = file_get_contents($file);
    if (empty(trim($sql))) continue;

    // Split by semicolons for individual execution
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );

    $count = 0;
    foreach ($statements as $stmt) {
        try {
            $db->exec($stmt);
            $count++;
        } catch (\Throwable $e) {
            echo "Error in $file: " . $e->getMessage() . "\n";
        }
    }
    echo "Executed $count statements from $file\n";
}

echo "Migration complete.\n";
