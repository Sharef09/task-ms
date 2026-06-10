<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Services\AuthService;

class AuthController
{
    private Database $db;
    private Session $session;
    private AuthService $authService;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->authService = new AuthService();
    }

    public function login(): void
    {
        if ($this->session->has('user')) {
            redirect('dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                    flash('error', 'Invalid security token');
                    redirect('login');
                    return;
                }

                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);

                if (empty($username) || empty($password)) {
                    flash('error', 'Please enter username and password');
                    redirect('login');
                    return;
                }

                $result = $this->authService->login($username, $password, $remember);

                if ($result['success']) {
                    flash('success', 'Welcome back, ' . $result['user']->first_name);
                    redirect('dashboard');
                } else {
                    flash('error', $result['message']);
                    redirect('login');
                }
            } catch (\Throwable $e) {
                logError('Login error', $e);
                flash('error', 'An error occurred. Please try again.');
                redirect('login');
            }
            return;
        }

        $pageTitle = 'Login';
        $content = __DIR__ . '/../views/auth/login.php';
        include dirname(__DIR__, 2) . '/layouts/auth-layout.php';
    }

    public function logout(): void
    {
        try {
            $this->authService->logout();
        } catch (\Throwable $e) {
            logError('Logout error', $e);
        }
        redirect('login');
    }

    public function forgotPassword(): void
    {
        if ($this->session->has('user')) {
            redirect('dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                    flash('error', 'Invalid security token');
                    redirect('forgot-password');
                    return;
                }

                $email = trim($_POST['email'] ?? '');

                if (empty($email)) {
                    flash('error', 'Please enter your email');
                    redirect('forgot-password');
                    return;
                }

                $result = $this->authService->sendOtp($email);

                if ($result['success']) {
                    $this->session->set('reset_user_id', $result['user_id']);
                    flash('success', 'OTP sent to your email');
                    redirect('verify-otp');
                } else {
                    flash('error', $result['message']);
                    redirect('forgot-password');
                }
            } catch (\Throwable $e) {
                logError('Forgot password error', $e);
                flash('error', 'An error occurred. Please try again.');
                redirect('forgot-password');
            }
            return;
        }

        $pageTitle = 'Forgot Password';
        $content = __DIR__ . '/../views/auth/forgot-password.php';
        include dirname(__DIR__, 2) . '/layouts/auth-layout.php';
    }

    public function verifyOtp(): void
    {
        if ($this->session->has('user')) {
            redirect('dashboard');
            return;
        }

        if (!$_SERVER['REQUEST_METHOD'] === 'GET' && !$this->session->has('reset_user_id')) {
            redirect('forgot-password');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                    flash('error', 'Invalid security token');
                    redirect('verify-otp');
                    return;
                }

                $otp = trim($_POST['otp'] ?? '');
                $userId = (int) $this->session->get('reset_user_id', 0);

                if (empty($otp) || $userId === 0) {
                    flash('error', 'Invalid request');
                    redirect('verify-otp');
                    return;
                }

                $result = $this->authService->verifyOtp($userId, $otp);

                if ($result['success']) {
                    flash('success', 'OTP verified. Please set a new password.');
                    redirect('reset-password');
                } else {
                    flash('error', $result['message']);
                    redirect('verify-otp');
                }
            } catch (\Throwable $e) {
                logError('OTP verification error', $e);
                flash('error', 'An error occurred. Please try again.');
                redirect('verify-otp');
            }
            return;
        }

        $pageTitle = 'Verify OTP';
        $content = __DIR__ . '/../views/auth/verify-otp.php';
        include dirname(__DIR__, 2) . '/layouts/auth-layout.php';
    }

    public function resetPassword(): void
    {
        if ($this->session->has('user')) {
            redirect('dashboard');
            return;
        }

        if (!$this->session->has('otp_verified')) {
            redirect('forgot-password');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                    flash('error', 'Invalid security token');
                    redirect('reset-password');
                    return;
                }

                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $userId = (int) $this->session->get('otp_verified', 0);

                if (empty($password) || strlen($password) < 8) {
                    flash('error', 'Password must be at least 8 characters');
                    redirect('reset-password');
                    return;
                }

                if ($password !== $confirmPassword) {
                    flash('error', 'Passwords do not match');
                    redirect('reset-password');
                    return;
                }

                $result = $this->authService->resetPassword($userId, $password);

                if ($result['success']) {
                    $this->session->remove('reset_user_id');
                    flash('success', 'Password reset successfully. Please login.');
                    redirect('login');
                } else {
                    flash('error', $result['message']);
                    redirect('reset-password');
                }
            } catch (\Throwable $e) {
                logError('Reset password error', $e);
                flash('error', 'An error occurred. Please try again.');
                redirect('reset-password');
            }
            return;
        }

        $pageTitle = 'Reset Password';
        $content = __DIR__ . '/../views/auth/reset-password.php';
        include dirname(__DIR__, 2) . '/layouts/auth-layout.php';
    }
}
