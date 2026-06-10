<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\SystemSetting;
use App\Services\EmailService;

class SettingController
{
    private Database $db;
    private Session $session;
    private SystemSetting $settingModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->settingModel = new SystemSetting();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $settings = [];
            foreach ($this->settingModel->getAll() as $s) {
                $settings[$s->setting_key] = $s->setting_value;
            }

            $pageTitle = 'Settings';
            $content = __DIR__ . '/../views/settings/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Settings index error', $e);
            flash('error', 'Unable to load settings');
            redirect('dashboard');
        }
    }

    public function updateGeneral(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('settings');
                return;
            }

            $data = [
                'app_name' => trim($_POST['app_name'] ?? ''),
                'app_url' => trim($_POST['app_url'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'company_address' => trim($_POST['company_address'] ?? ''),
                'company_phone' => trim($_POST['company_phone'] ?? ''),
                'company_email' => trim($_POST['company_email'] ?? ''),
                'timezone' => $_POST['timezone'] ?? 'UTC',
                'date_format' => $_POST['date_format'] ?? 'M j, Y',
                'time_format' => $_POST['time_format'] ?? 'g:i A',
                'items_per_page' => $_POST['items_per_page'] ?? '15',
            ];

            $this->settingModel->setMultiple($data);

            logActivity('Update General Settings', 'Settings');
            flash('success', 'General settings updated successfully');
            redirect('settings');

        } catch (\Throwable $e) {
            logError('Update general settings error', $e);
            flash('error', 'Failed to update general settings');
            redirect('settings');
        }
    }

    public function updateEmail(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('settings');
                return;
            }

            $data = [
                'mail_host' => trim($_POST['mail_host'] ?? ''),
                'mail_port' => trim($_POST['mail_port'] ?? '587'),
                'mail_username' => trim($_POST['mail_username'] ?? ''),
                'mail_password' => $_POST['mail_password'] ?? '',
                'mail_encryption' => $_POST['mail_encryption'] ?? 'tls',
                'mail_from_email' => trim($_POST['mail_from_email'] ?? ''),
                'mail_from_name' => trim($_POST['mail_from_name'] ?? ''),
            ];

            $this->settingModel->setMultiple($data);

            logActivity('Update Email Settings', 'Settings');
            flash('success', 'Email settings updated successfully');
            redirect('settings');

        } catch (\Throwable $e) {
            logError('Update email settings error', $e);
            flash('error', 'Failed to update email settings');
            redirect('settings');
        }
    }

    public function updateSecurity(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('settings');
                return;
            }

            $data = [
                'max_login_attempts' => (string)((int)($_POST['max_login_attempts'] ?? 5)),
                'session_lifetime' => (string)((int)($_POST['session_lifetime'] ?? 3600)),
                'password_min_length' => (string)((int)($_POST['password_min_length'] ?? 8)),
                'password_require_special' => isset($_POST['password_require_special']) ? '1' : '0',
                'password_require_numbers' => isset($_POST['password_require_numbers']) ? '1' : '0',
                'two_factor_auth' => isset($_POST['two_factor_auth']) ? '1' : '0',
                'account_lockout_duration' => (string)((int)($_POST['account_lockout_duration'] ?? 15)),
            ];

            $this->settingModel->setMultiple($data);

            logActivity('Update Security Settings', 'Settings');
            flash('success', 'Security settings updated successfully');
            redirect('settings');

        } catch (\Throwable $e) {
            logError('Update security settings error', $e);
            flash('error', 'Failed to update security settings');
            redirect('settings');
        }
    }

    public function sendTestEmail(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('settings');
                return;
            }

            $user = $this->session->get('user');
            $emailService = new EmailService();
            $success = $emailService->sendTestEmail($user->email);

            if ($success) {
                flash('success', 'Test email sent to ' . $user->email);
            } else {
                flash('error', 'Failed to send test email. Check email settings.');
            }

            redirect('settings');

        } catch (\Throwable $e) {
            logError('Send test email error', $e);
            flash('error', 'Failed to send test email');
            redirect('settings');
        }
    }
}
