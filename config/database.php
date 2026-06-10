<?php

$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    $host     = $parsed['host'] ?? 'localhost';
    $port     = $parsed['port'] ?? '3306';
    $dbname   = ltrim($parsed['path'] ?? 'task_management', '/');
    $username = $parsed['user'] ?? 'root';
    $password = $parsed['pass'] ?? '';
} else {
    $host     = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: getenv('MARIADB_HOST') ?: 'localhost';
    $port     = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: getenv('MARIADB_PORT') ?: '3306';
    $dbname   = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('MARIADB_DATABASE') ?: 'task_management';
    $username = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: getenv('MARIADB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: getenv('MARIADB_PASSWORD') ?: '';
}

return [
    'host'     => $host,
    'port'     => $port,
    'dbname'   => $dbname,
    'username' => $username,
    'password' => $password,
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
