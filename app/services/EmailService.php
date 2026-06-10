<?php

namespace App\Services;

use App\Helpers\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private Database $db;
    private ?object $settings;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->settings = $this->loadSettings();
    }

    public function sendOtpEmail(string $to, string $name, string $otp): bool
    {
        $template = $this->getTemplate('password_reset_otp');
        $body = $template ? str_replace(['{{name}}', '{{otp}}', '{{expiry}}'], [$name, $otp, '10 minutes'], $template->body) : "Your OTP is: {$otp}";
        $subject = $template ? $template->subject : 'Password Reset OTP';

        return $this->send($to, $subject, $body);
    }

    public function sendTaskAssignment(string $to, string $name, array $taskData): bool
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $loginUrl = rtrim($app['url'], '/') . '/login';
        $priorityColor = $this->getPriorityColor($taskData['priority']);

        $template = $this->getTemplate('task_assigned');
        if ($template) {
            $body = str_replace(
                ['{{name}}', '{{task_number}}', '{{task_title}}', '{{priority}}', '{{priority_color}}', '{{due_date}}', '{{assigned_by}}', '{{login_url}}'],
                [$name, $taskData['task_number'], $taskData['title'], $taskData['priority'], $priorityColor, $taskData['due_date'], $taskData['assigned_by'], $loginUrl],
                $template->body
            );
            $subject = $template->subject;
        } else {
            $subject = "New Task Assigned: {$taskData['task_number']}";
            $body = "Hello {$name},<br><br>You have been assigned a new task:<br>
                     <b>Task:</b> {$taskData['task_number']} - {$taskData['title']}<br>
                     <b>Priority:</b> <span style=\"color:{$priorityColor}\">{$taskData['priority']}</span><br>
                     <b>Due Date:</b> {$taskData['due_date']}<br>
                     <b>Assigned By:</b> {$taskData['assigned_by']}<br><br>
                     <a href=\"{$loginUrl}\">Login to view task</a>";
        }

        return $this->send($to, $subject, $body);
    }

    public function sendTaskReassignment(string $to, string $name, array $taskData, string $previousAssignee): bool
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $loginUrl = rtrim($app['url'], '/') . '/login';

        $subject = "Task Reassigned: {$taskData['task_number']}";
        $body = "Hello {$name},<br><br>
                 Task <b>{$taskData['task_number']}</b> - {$taskData['title']} has been reassigned from {$previousAssignee} to you.<br>
                 <b>Priority:</b> {$taskData['priority']}<br>
                 <b>Due Date:</b> {$taskData['due_date']}<br><br>
                 <a href=\"{$loginUrl}\">Login to view task</a>";

        return $this->send($to, $subject, $body);
    }

    public function sendTaskCompleted(string $to, string $name, array $taskData): bool
    {
        $subject = "Task Completed: {$taskData['task_number']}";
        $body = "Hello {$name},<br><br>
                 Task <b>{$taskData['task_number']}</b> - {$taskData['title']} has been marked as completed.<br>
                 Completed by: {$taskData['completed_by']}<br>
                 Actual Hours: {$taskData['actual_hours']}<br><br>
                 <a href=\"{$this->getLoginUrl()}\">Login to view</a>";

        return $this->send($to, $subject, $body);
    }

    public function sendTaskOverdue(string $to, string $name, array $taskData): bool
    {
        $subject = "Task Overdue: {$taskData['task_number']}";
        $body = "Hello {$name},<br><br>
                 Task <b>{$taskData['task_number']}</b> - {$taskData['title']} is now overdue.<br>
                 Due Date: {$taskData['due_date']}<br>
                 Priority: {$taskData['priority']}<br><br>
                 Please take immediate action.<br><br>
                 <a href=\"{$this->getLoginUrl()}\">Login to view task</a>";

        return $this->send($to, $subject, $body);
    }

    public function sendNewUserCredentials(string $to, string $name, string $username, string $password): bool
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $loginUrl = rtrim($app['url'], '/') . '/login';

        $subject = "Your Account Has Been Created";
        $body = "Hello {$name},<br><br>
                 Your account has been created in the Task Management System.<br><br>
                 <b>Username:</b> {$username}<br>
                 <b>Password:</b> {$password}<br><br>
                 Please login and change your password.<br><br>
                 <a href=\"{$loginUrl}\">Login Here</a>";

        return $this->send($to, $subject, $body);
    }

    public function sendSystemAlert(string $to, string $name, string $alertMessage): bool
    {
        $subject = "System Alert";
        $body = "Hello {$name},<br><br>{$alertMessage}";

        return $this->send($to, $subject, $body);
    }

    public function sendTestEmail(string $to): bool
    {
        $subject = "Test Email from Task Management System";
        $body = "This is a test email to confirm your SMTP configuration is working correctly.<br><br>
                 Sent at: " . date('Y-m-d H:i:s');

        return $this->send($to, $subject, $body);
    }

    public function sendWithAttachment(string $to, string $subject, string $body, string $attachmentContent, string $filename, string $mimeType = 'text/csv'): bool
    {
        if (!$this->settings || empty($this->settings->mail_host)) {
            logError('Email settings not configured');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->settings->mail_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->settings->mail_username ?? '';
            $mail->Password   = $this->settings->mail_password ?? '';
            $mail->SMTPSecure = $this->settings->mail_encryption ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->settings->mail_port ?? 587;

            $fromEmail = $this->settings->mail_from_email ?? 'noreply@taskms.com';
            $fromName  = $this->settings->mail_from_name ?? 'Task Management System';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $this->wrapInTemplate($body);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $attachment = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($attachment, $attachmentContent);
            $mail->addAttachment($attachment, $filename, 'base64', $mimeType);

            $mail->send();
            @unlink($attachment);

            $this->logEmail($to, $subject, 'sent');
            return true;
        } catch (Exception $e) {
            logError("Email send failed to {$to}: " . $e->getMessage());
            $this->logEmail($to, $subject, 'failed', $e->getMessage());
            return false;
        }
    }

    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        if (!$this->settings || empty($this->settings->mail_host)) {
            logError('Email settings not configured');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->settings->mail_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->settings->mail_username ?? '';
            $mail->Password   = $this->settings->mail_password ?? '';
            $mail->SMTPSecure = $this->settings->mail_encryption ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->settings->mail_port ?? 587;

            $fromEmail = $this->settings->mail_from_email ?? 'noreply@taskms.com';
            $fromName  = $this->settings->mail_from_name ?? 'Task Management System';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if ($isHtml) {
                $mail->isHTML(true);
                $body = $this->wrapInTemplate($body);
            }

            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $mail->send();

            $this->logEmail($to, $subject, 'sent');
            return true;
        } catch (Exception $e) {
            logError("Email send failed to {$to}: " . $e->getMessage());
            $this->logEmail($to, $subject, 'failed', $e->getMessage());
            return false;
        }
    }

    private function wrapInTemplate(string $body): string
    {
        return '<!DOCTYPE html>
        <html><head><meta charset="UTF-8"><style>
            body { font-family: Segoe UI, Tahoma, sans-serif; background: #f9fafb; margin: 0; padding: 0; }
            .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .email-header { background: #1e2a3a; padding: 20px; text-align: center; }
            .email-header h1 { color: #ffffff; margin: 0; font-size: 20px; }
            .email-body { padding: 30px; color: #1e293b; font-size: 14px; line-height: 1.6; }
            .email-footer { background: #f1f5f9; padding: 15px; text-align: center; color: #64748b; font-size: 12px; }
        </style></head>
        <body><div class="email-container">
            <div class="email-header"><h1>Task Management System</h1></div>
            <div class="email-body">' . $body . '</div>
            <div class="email-footer">&copy; ' . date('Y') . ' Task Management System. All rights reserved.</div>
        </div></body></html>';
    }

    private function getTemplate(string $type): ?object
    {
        return $this->db->fetch(
            "SELECT * FROM email_templates WHERE template_type = ? AND status = 'active'",
            [$type]
        );
    }

    private function logEmail(string $to, string $subject, string $status, ?string $error = null): void
    {
        $this->db->insert('email_logs', [
            'recipient'  => $to,
            'subject'    => $subject,
            'status'     => $status,
            'error_message' => $error,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function loadSettings(): ?object
    {
        $settings = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'mail_%'"
        );
        if (empty($settings)) {
            return null;
        }
        $result = new \stdClass();
        foreach ($settings as $s) {
            $result->{$s->setting_key} = $s->setting_value;
        }
        return $result;
    }

    private function getLoginUrl(): string
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        return rtrim($app['url'], '/') . '/login';
    }

    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'Low'      => '#16a34a',
            'Medium'   => '#2563eb',
            'High'     => '#d97706',
            'Critical' => '#dc2626',
            default    => '#64748b',
        };
    }
}
