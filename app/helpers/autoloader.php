<?php

$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\Controllers\\' => dirname(__DIR__) . '/controllers/',
        'App\\Models\\'      => dirname(__DIR__) . '/models/',
        'App\\Services\\'    => dirname(__DIR__) . '/services/',
        'App\\Helpers\\'     => dirname(__DIR__) . '/helpers/',
        'App\\Middleware\\'  => dirname(__DIR__) . '/middleware/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
