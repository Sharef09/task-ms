<?php
header('Content-Type: text/plain');

echo "=== Deployed Code Check ===\n\n";

$file = __DIR__ . '/../includes/sidebar.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'str_starts_with') !== false) {
        echo "sidebar.php: OLD CODE (str_starts_with found)\n";
    } elseif (strpos($content, 'strpos($currentUri') !== false) {
        echo "sidebar.php: NEW CODE \xE2\x9C\x93 (strpos found)\n";
    }
}

$file2 = __DIR__ . '/../database/schema.sql';
if (file_exists($file2)) {
    $content2 = file_get_contents($file2);
    if (strpos($content2, 'user_preferences') !== false) {
        echo "schema.sql: HAS user_preferences table\n";
    } else {
        echo "schema.sql: MISSING user_preferences table\n";
    }
}
