<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\User;

class ProfileController
{
    private Database $db;
    private Session $session;
    private User $userModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->userModel = new User();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $user = $this->session->get('user');
            $profile = $this->userModel->getById($user->id);

            if (!$profile) {
                flash('error', 'User not found');
                redirect('logout');
                return;
            }

            $prefs = $this->db->fetch(
                "SELECT preferences FROM user_preferences WHERE user_id = ?",
                [$user->id]
            );
            $preferences = $prefs ? json_decode($prefs->preferences, true) : [];

            $pageTitle = 'My Profile';
            $content = __DIR__ . '/../views/profile/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Profile index error', $e);
            flash('error', 'Unable to load profile');
            redirect('dashboard');
        }
    }

    public function update(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('profile');
                return;
            }

            $user = $this->session->get('user');

            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'mobile' => trim($_POST['mobile'] ?? ''),
            ];

            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                flash('error', 'Required fields are missing');
                redirect('profile');
                return;
            }

            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL",
                [$data['email'], $user->id]
            );
            if ($existing) {
                flash('error', 'Email already in use');
                redirect('profile');
                return;
            }

            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = $_FILES['avatar'];
                $ext = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $avatarName = 'staff_' . ($user->employee_id ?? $user->id) . '.' . $ext;
                    $publicPath = dirname(__DIR__, 2) . '/public/storage/uploads/avatars/';

                    if (!is_dir($publicPath)) {
                        mkdir($publicPath, 0775, true);
                    }

                    if (move_uploaded_file($avatar['tmp_name'], $publicPath . $avatarName)) {
                        $data['avatar'] = 'storage/uploads/avatars/' . $avatarName;

                        if ($user->avatar) {
                            $oldPublic = dirname(__DIR__, 2) . '/public/' . $user->avatar;
                            $oldPrivate = dirname(__DIR__, 2) . '/' . $user->avatar;
                            if (file_exists($oldPublic)) unlink($oldPublic);
                            if (file_exists($oldPrivate)) unlink($oldPrivate);
                        }
                    }
                }
            }

            $this->userModel->update($user->id, $data);

            $updatedUser = $this->userModel->getById($user->id);
            $sessionUser = clone $updatedUser;
            unset($sessionUser->password);
            $this->session->set('user', $sessionUser);

            logActivity('Update Profile', 'Profile', $user->id);
            flash('success', 'Profile updated successfully');
            redirect('profile');

        } catch (\Throwable $e) {
            logError('Profile update error', $e);
            flash('error', 'Failed to update profile');
            redirect('profile');
        }
    }

    public function changePassword(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('profile');
                return;
            }

            $user = $this->session->get('user');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $stored = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$user->id]);
            if (!$stored || !password_verify($currentPassword, $stored->password)) {
                flash('error', 'Current password is incorrect');
                redirect('profile');
                return;
            }

            if (strlen($newPassword) < 8) {
                flash('error', 'New password must be at least 8 characters');
                redirect('profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                flash('error', 'Passwords do not match');
                redirect('profile');
                return;
            }

            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->db->update('users', ['password' => $hashed], 'id = ?', [$user->id]);

            logActivity('Change Password', 'Profile', $user->id);
            flash('success', 'Password changed successfully');
            redirect('profile');

        } catch (\Throwable $e) {
            logError('Change password error', $e);
            flash('error', 'Failed to change password');
            redirect('profile');
        }
    }

    public function loginHistory(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $logs = $this->db->fetchAll(
                "SELECT * FROM login_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [$user->id, $perPage, $offset]
            );
            $total = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM login_logs WHERE user_id = ?",
                [$user->id]
            )->cnt ?? 0;
            $totalPages = ceil($total / $perPage);

            $pageTitle = 'Login History';
            $content = __DIR__ . '/../views/profile/login-history.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Login history error', $e);
            flash('error', 'Unable to load login history');
            redirect('profile');
        }
    }

    public function activityHistory(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $logs = $this->db->fetchAll(
                "SELECT al.*, u.first_name, u.last_name
                 FROM activity_logs al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE al.user_id = ?
                 ORDER BY al.created_at DESC LIMIT ? OFFSET ?",
                [$user->id, $perPage, $offset]
            );
            $total = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM activity_logs WHERE user_id = ?",
                [$user->id]
            )->cnt ?? 0;
            $totalPages = ceil($total / $perPage);

            $pageTitle = 'Activity History';
            $content = __DIR__ . '/../views/profile/activity-history.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Activity history error', $e);
            flash('error', 'Unable to load activity history');
            redirect('profile');
        }
    }

    public function notificationPreferences(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                    flash('error', 'Invalid security token');
                    redirect('profile?tab=notifications');
                    return;
                }

                $user = $this->session->get('user');
                $preferences = [
                    'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                    'browser_notifications' => isset($_POST['browser_notifications']) ? 1 : 0,
                    'task_assigned' => isset($_POST['task_assigned']) ? 1 : 0,
                    'task_completed' => isset($_POST['task_completed']) ? 1 : 0,
                    'task_overdue' => isset($_POST['task_overdue']) ? 1 : 0,
                    'mention_notifications' => isset($_POST['mention_notifications']) ? 1 : 0,
                ];

                $this->db->query(
                    "INSERT INTO user_preferences (user_id, preferences, updated_at)
                     VALUES (?, ?, NOW())
                     ON DUPLICATE KEY UPDATE preferences = ?, updated_at = NOW()",
                    [$user->id, json_encode($preferences), json_encode($preferences)]
                );

                logActivity('Update Notification Preferences', 'Profile', $user->id);
                flash('success', 'Notification preferences updated');
                redirect('profile?tab=notifications');
                return;
            }

            $user = $this->session->get('user');
            $prefs = $this->db->fetch(
                "SELECT preferences FROM user_preferences WHERE user_id = ?",
                [$user->id]
            );
            $preferences = $prefs ? json_decode($prefs->preferences, true) : [];

            $pageTitle = 'Notification Preferences';
            $content = __DIR__ . '/../views/profile/notification-preferences.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Notification preferences error', $e);
            flash('error', 'Unable to load preferences');
            redirect('profile');
        }
    }
}
