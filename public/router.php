<?php
/**
 * Router script for PHP built-in development server.
 * Mimics .htaccess rewrites: serve existing files, route everything else to index.php.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__;

// If the file exists, serve it
$filePath = $publicDir . $uri;
if ($uri !== '/' && file_exists($filePath)) {
    return false;
}

// Route everything else through index.php
require $publicDir . '/index.php';
