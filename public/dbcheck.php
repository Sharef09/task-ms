<?php
header('Content-Type: text/plain');

echo "=== Deployed Code Check ===\n\n";

$file = __DIR__ . '/../includes/sidebar.php';
echo "File: $file\n";
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'str_starts_with') !== false) {
        echo "STATUS: OLD CODE (str_starts_with found)\n";
    } elseif (strpos($content, 'strpos($currentUri') !== false) {
        echo "STATUS: NEW CODE ✓ (strpos found)\n";
    } else {
        echo "STATUS: Unknown version\n";
    }
    echo "Size: " . strlen($content) . " bytes\n";
    echo "Lines: " . substr_count($content, "\n") . "\n";
} else {
    echo "sidebar.php not found\n";
}
