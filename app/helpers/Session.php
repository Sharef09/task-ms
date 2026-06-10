<?php

namespace App\Helpers;

class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            $sessionConfig = $app['session'];

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', $sessionConfig['samesite']);

            if ($sessionConfig['secure']) {
                ini_set('session.cookie_secure', '1');
            }

            session_set_cookie_params([
                'lifetime' => $sessionConfig['lifetime'],
                'path'     => '/',
                'domain'   => '',
                'secure'   => $sessionConfig['secure'],
                'httponly' => $sessionConfig['httponly'],
                'samesite' => $sessionConfig['samesite'],
            ]);

            session_name('TMS_SESSION');
            session_start();
            $this->checkTimeout($sessionConfig['lifetime']);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function setFlash(string $key, string $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key): ?string
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    private function checkTimeout(int $lifetime): void
    {
        if (isset($_SESSION['_last_activity'])) {
            $inactive = time() - $_SESSION['_last_activity'];
            if ($inactive > $lifetime) {
                $this->destroy();
                header('Location: ' . $this->getLoginUrl());
                exit;
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    private function getLoginUrl(): string
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        return rtrim($app['url'], '/') . '/login';
    }
}
