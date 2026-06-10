<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Session;

class AuthService
{
    private Database $db;
    private Session $session;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
    }

    public function login(string $username, string $password, bool $remember = false): array
    {
        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE (u.username = ? OR u.email = ? OR u.employee_id = ?) AND u.deleted_at IS NULL",
            [$username, $username, $username]
        );

        if (!$user) {
            $this->logLoginAttempt($username, null, false);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if ($user->status === 'Inactive' || $user->status === 'Suspended') {
            $this->logLoginAttempt($username, $user->id, false);
            return ['success' => false, 'message' => 'Your account is ' . strtolower($user->status)];
        }

        if ($user->status === 'Locked') {
            $this->logLoginAttempt($username, $user->id, false);
            return ['success' => false, 'message' => 'Your account has been locked. Contact an administrator.'];
        }

        if (!password_verify($password, $user->password)) {
            $this->incrementFailedAttempts($user->id);
            $this->logLoginAttempt($username, $user->id, false);

            $maxAttempts = $this->getMaxLoginAttempts();
            if ($user->failed_attempts + 1 >= $maxAttempts) {
                $this->lockAccount($user->id);
                $this->logLoginAttempt($username, $user->id, false);
                logActivity('Account Lock', 'Auth', $user->id, null, json_encode(['reason' => 'Max failed attempts']));
                return ['success' => false, 'message' => 'Account locked due to too many failed attempts'];
            }

            $remaining = $maxAttempts - ($user->failed_attempts + 1);
            return ['success' => false, 'message' => "Invalid credentials. {$remaining} attempt(s) remaining."];
        }

        $this->resetFailedAttempts($user->id);
        $this->session->regenerate();

        $userData = clone $user;
        unset($userData->password);
        $this->session->set('user', $userData);
        $this->session->set('_last_activity', time());

        $this->logLoginAttempt($username, $user->id, true, true);
        logActivity('Login', 'Auth', $user->id);

        if ($remember) {
            $this->createRememberToken($user->id);
        }

        return ['success' => true, 'user' => $user];
    }

    public function logout(): void
    {
        $user = $this->session->get('user');
        if ($user) {
            logActivity('Logout', 'Auth', $user->id);
        }

        $this->clearRememberToken();
        $this->session->destroy();
    }

    public function attemptRememberMeLogin(): bool
    {
        $token = $_COOKIE['tms_remember'] ?? null;
        if (!$token) {
            return false;
        }

        $hash = hash('sha256', $token);
        $record = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             JOIN remember_tokens rt ON u.id = rt.user_id
             JOIN roles r ON u.role_id = r.id
             WHERE rt.token_hash = ? AND rt.expires_at > NOW() AND u.deleted_at IS NULL",
            [$hash]
        );

        if (!$record) {
            return false;
        }

        $userData = clone $record;
        unset($userData->password);
        $this->session->set('user', $userData);
        $this->session->set('_last_activity', time());

        $this->extendRememberToken($record->id);
        return true;
    }

    public function sendOtp(string $email): array
    {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }

        $recent = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM otp_codes
             WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$email]
        );

        if ($recent && $recent->cnt >= 3) {
            return ['success' => false, 'message' => 'Too many requests. Please try again later.'];
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        $this->db->insert('otp_codes', [
            'user_id'    => $user->id,
            'email'      => $email,
            'otp'        => password_hash($otp, PASSWORD_BCRYPT),
            'expires_at' => $expiresAt,
            'attempts'   => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        logActivity('OTP Sent', 'Auth', $user->id);

        $settings = $this->getEmailSettings();
        if ($settings && $settings['host']) {
            $mailService = new EmailService();
            $mailService->sendOtpEmail($email, $user->first_name . ' ' . $user->last_name, $otp);
        }

        return ['success' => true, 'message' => 'OTP sent to your email', 'user_id' => $user->id];
    }

    public function verifyOtp(int $userId, string $otp): array
    {
        $record = $this->db->fetch(
            "SELECT * FROM otp_codes
             WHERE user_id = ? AND used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        if (!$record) {
            return ['success' => false, 'message' => 'OTP expired or not found'];
        }

        if ($record->attempts >= 5) {
            $this->db->update('otp_codes', ['used' => 1, 'attempts' => $record->attempts + 1], 'id = ?', [$record->id]);
            return ['success' => false, 'message' => 'OTP invalidated due to too many attempts'];
        }

        if (!password_verify($otp, $record->otp)) {
            $this->db->update('otp_codes', ['attempts' => $record->attempts + 1], 'id = ?', [$record->id]);
            logActivity('OTP Failed', 'Auth', $userId);
            return ['success' => false, 'message' => 'Invalid OTP'];
        }

        $this->db->update('otp_codes', ['used' => 1], 'id = ?', [$record->id]);
        $this->session->set('otp_verified', $userId);

        logActivity('OTP Verified', 'Auth', $userId);

        return ['success' => true, 'message' => 'OTP verified'];
    }

    public function resetPassword(int $userId, string $newPassword): array
    {
        if ($this->session->get('otp_verified') !== $userId) {
            return ['success' => false, 'message' => 'Unauthorized password reset'];
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->update('users', ['password' => $hashed], 'id = ?', [$userId]);
        $this->session->remove('otp_verified');

        logActivity('Password Reset', 'Auth', $userId);

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    private function logLoginAttempt(string $username, ?int $userId, bool $success, bool $isLogin = false): void
    {
        $this->db->insert('login_logs', [
            'user_id'     => $userId,
            'username'    => $username,
            'status'      => $success ? 'success' : 'failed',
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    private function incrementFailedAttempts(int $userId): void
    {
        $this->db->query("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?", [$userId]);
    }

    private function resetFailedAttempts(int $userId): void
    {
        $this->db->update('users', ['failed_attempts' => 0], 'id = ?', [$userId]);
    }

    private function lockAccount(int $userId): void
    {
        $this->db->update('users', ['status' => 'Locked'], 'id = ?', [$userId]);
    }

    private function getMaxLoginAttempts(): int
    {
        $setting = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'max_login_attempts'");
        return $setting ? (int)$setting->setting_value : 5;
    }

    private function getEmailSettings(): ?object
    {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'mail_%'");
        $result = new \stdClass();
        foreach ($settings as $s) {
            $result->{$s->setting_key} = $s->setting_value;
        }
        return $result;
    }

    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 86400 * 30);

        $this->db->query("DELETE FROM remember_tokens WHERE user_id = ?", [$userId]);

        $this->db->insert('remember_tokens', [
            'user_id'    => $userId,
            'token_hash' => $hash,
            'expires_at' => $expires,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $secure = $app['session']['secure'] ?? false;

        setcookie('tms_remember', $token, time() + 86400 * 30, '/', '', $secure, true);
    }

    public function clearRememberToken(): void
    {
        if (isset($_COOKIE['tms_remember'])) {
            $hash = hash('sha256', $_COOKIE['tms_remember']);
            $this->db->query("DELETE FROM remember_tokens WHERE token_hash = ?", [$hash]);
            setcookie('tms_remember', '', time() - 3600, '/');
        }
    }

    private function extendRememberToken(int $userId): void
    {
        $newExpires = date('Y-m-d H:i:s', time() + 86400 * 30);
        $this->db->update('remember_tokens', ['expires_at' => $newExpires], 'user_id = ?', [$userId]);
    }
}
