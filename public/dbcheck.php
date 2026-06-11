<?php
// Debug page - accessible directly via browser
header('Content-Type: text/plain');

echo "=== DB Config ===\n";
$cfg = require __DIR__ . '/../config/database.php';
echo "MYSQL_URL: " . (getenv('MYSQL_URL') ?: '(not set)') . "\n";
echo "MYSQL_PUBLIC_URL: " . (getenv('MYSQL_PUBLIC_URL') ?: '(not set)') . "\n";
echo "Host: {$cfg['host']}\n";
echo "Port: {$cfg['port']}\n";
echo "DB: {$cfg['dbname']}\n";
echo "User: {$cfg['username']}\n";
echo "Pass: " . (strlen($cfg['password']) > 0 ? '***' : '(empty)') . "\n";

echo "\n=== Env vars ===\n";
foreach (['MYSQL_URL', 'MYSQL_PUBLIC_URL', 'MYSQLHOST', 'MYSQL_DATABASE', 'MYSQLUSER', 'MYSQL_ROOT_PASSWORD', 'MYSQLPORT', 'RAILWAY_PRIVATE_DOMAIN'] as $k) {
    echo "$k: " . (getenv($k) ?: '(not set)') . "\n";
}

echo "\n=== Test DB Connection ===\n";
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']);
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
    echo "Connection successful!\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = '{$cfg['dbname']}'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Tables in database: {$row->cnt}\n";
} catch (Throwable $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== PHP Info (PDO drivers) ===\n";
echo "PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
