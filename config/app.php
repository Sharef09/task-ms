<?php

return [
    'name'    => getenv('APP_NAME') ?: 'Task Management System',
    'url'     => getenv('APP_URL') ?: 'http://localhost/task-ms',
    'env'     => getenv('APP_ENV') ?: 'production',
    'debug'   => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) ?: false,
    'timezone'=> 'UTC',
    'session' => [
        'lifetime' => 1800,
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    'pagination' => [
        'per_page' => 25,
    ],
    'upload' => [
        'max_size'    => 10485760,
        'allowed_extensions' => ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'jpeg'],
        'allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/png',
            'image/jpeg',
        ],
        'path' => dirname(__DIR__) . '/storage/uploads',
    ],
    'backup' => [
        'path' => dirname(__DIR__) . '/storage/backups',
    ],
    'log' => [
        'path' => dirname(__DIR__) . '/storage/logs',
        'file' => 'app.log',
    ],
];
