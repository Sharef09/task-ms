<?php

use App\Helpers\Database;
use App\Helpers\Session;

function db(): Database
{
    return Database::getInstance();
}

function session(): Session
{
    return Session::getInstance();
}

function redirect(string $path): void
{
    $app = require dirname(__DIR__, 2) . '/config/app.php';
    header('Location: ' . rtrim($app['url'], '/') . '/' . ltrim($path, '/'));
    exit;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf(string $token): bool
{
    return hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}

function e(mixed $value): string
{
    if ($value === null || $value === false) return '';
    if (is_array($value) || is_object($value)) {
        return htmlspecialchars(print_r($value, true), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function truncate(string $text, int $length = 50): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function timeAgo(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $timestamp);
}

function formatDate(?string $datetime, string $format = 'M j, Y'): string
{
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

function statusBadge(string $status): string
{
    $map = [
        'Active'      => 'success',
        'Inactive'    => 'secondary',
        'Suspended'   => 'warning',
        'Locked'      => 'danger',
        'Draft'       => 'secondary',
        'Open'        => 'info',
        'Assigned'    => 'primary',
        'In Progress' => 'warning',
        'Waiting'     => 'secondary',
        'On Hold'     => 'dark',
        'Completed'   => 'success',
        'Cancelled'   => 'danger',
        'Overdue'     => 'danger',
    ];

    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e($status) . '</span>';
}

function priorityBadge(string $priority): string
{
    $map = [
        'Low'      => 'success',
        'Medium'   => 'info',
        'High'     => 'warning',
        'Critical' => 'danger',
    ];

    $class = $map[$priority] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e($priority) . '</span>';
}

function asset(string $path): string
{
    $app = require dirname(__DIR__, 2) . '/config/app.php';
    return rtrim($app['url'], '/') . '/assets/' . ltrim($path, '/');
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function hasPermission(string $permission): bool
{
    if (!isset($_SESSION['user'])) {
        return false;
    }

    $user = $_SESSION['user'];
    if ($user->role_slug === 'administrator') {
        return true;
    }

    $db = db();
    $perm = $db->fetch(
        "SELECT p.slug FROM permissions p
         JOIN role_permissions rp ON p.id = rp.permission_id
         WHERE rp.role_id = ? AND p.slug = ?",
        [$user->role_id, $permission]
    );

    if ($perm) {
        return true;
    }

    $userPerm = $db->fetch(
        "SELECT p.slug FROM permissions p
         JOIN user_permissions up ON p.id = up.permission_id
         WHERE up.user_id = ? AND p.slug = ? AND up.granted = 1",
        [$user->id, $permission]
    );

    return $userPerm !== null;
}

function isAdmin(): bool
{
    return isset($_SESSION['user']) && ($_SESSION['user']->role_slug ?? '') === 'administrator';
}

function isManager(): bool
{
    return isset($_SESSION['user']) && ($_SESSION['user']->role_slug ?? '') === 'manager';
}

function isStaff(): bool
{
    return isset($_SESSION['user']) && ($_SESSION['user']->role_slug ?? '') === 'staff';
}

function logActivity(string $action, string $module, ?int $recordId = null, ?string $oldValue = null, ?string $newValue = null): void
{
    $user = $_SESSION['user'] ?? null;
    $db = db();

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device = 'Desktop';
    if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
        $device = 'Mobile';
    } elseif (preg_match('/Tablet|iPad/i', $userAgent)) {
        $device = 'Tablet';
    }

    $db->insert('activity_logs', [
        'user_id'    => $user->id ?? null,
        'action'     => $action,
        'module'     => $module,
        'record_id'  => $recordId,
        'old_value'  => $oldValue,
        'new_value'  => $newValue,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'user_agent' => $userAgent,
        'device'     => $device,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function generateTaskNumber(): string
{
    $db = db();
    $last = $db->fetch("SELECT task_number FROM tasks ORDER BY id DESC LIMIT 1");
    if ($last && preg_match('/TASK-(\d+)/', $last->task_number, $m)) {
        return 'TASK-' . str_pad((int)$m[1] + 1, 5, '0', STR_PAD_LEFT);
    }
    return 'TASK-00001';
}

function generateEmployeeId(): string
{
    $db = db();
    $last = $db->fetch("SELECT employee_id FROM users ORDER BY id DESC LIMIT 1");
    if ($last && preg_match('/EMP-(\d+)/', $last->employee_id, $m)) {
        return 'EMP-' . str_pad((int)$m[1] + 1, 4, '0', STR_PAD_LEFT);
    }
    return 'EMP-0001';
}

function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function logError(string $message, ?Throwable $exception = null): void
{
    $app = require dirname(__DIR__, 2) . '/config/app.php';
    $logFile = $app['log']['path'] . '/' . $app['log']['file'];
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($exception) {
        $entry .= ' | ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    }
    $entry .= PHP_EOL;

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function getStatusLabel(string $status): string
{
    return match($status) {
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
        'locked' => 'Locked',
        default => ucfirst($status),
    };
}
