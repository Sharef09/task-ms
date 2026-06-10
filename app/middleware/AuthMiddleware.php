<?php

namespace App\Middleware;

use App\Helpers\Session;

class AuthMiddleware
{
    public static function handle(): void
    {
        $session = Session::getInstance();
        if (!$session->has('user')) {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            header('Location: ' . rtrim($app['url'], '/') . '/login');
            exit;
        }
    }
}

class AdminMiddleware
{
    public static function handle(): void
    {
        AuthMiddleware::handle();
        $session = Session::getInstance();
        $user = $session->get('user');
        if (!$user || $user->role_slug !== 'administrator') {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            header('Location: ' . rtrim($app['url'], '/') . '/dashboard');
            exit;
        }
    }
}

class PermissionMiddleware
{
    public static function require(string $permission): void
    {
        AuthMiddleware::handle();
        if (!hasPermission($permission)) {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            header('Location: ' . rtrim($app['url'], '/') . '/dashboard');
            exit;
        }
    }
}
