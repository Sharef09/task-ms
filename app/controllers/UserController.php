<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Services\EmailService;

class UserController
{
    private Database $db;
    private Session $session;
    private User $userModel;
    private Role $roleModel;
    private Department $departmentModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->departmentModel = new Department();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'role_id' => $_GET['role_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
            ];

            $users = $this->userModel->getAll($page, $perPage, $filters);
            $total = $this->userModel->getTotalCount($filters);
            $totalPages = ceil($total / $perPage);

            $roles = $this->roleModel->getAll();
            $departments = $this->departmentModel->getAll();

            $pageTitle = 'Users';
            $content = __DIR__ . '/../views/users/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('User index error', $e);
            flash('error', 'Unable to load users');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $roles = $this->roleModel->getAll();
            $departments = $this->departmentModel->getAll();

            $pageTitle = 'Create User';
            $content = __DIR__ . '/../views/users/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('User create form error', $e);
            flash('error', 'Unable to load form');
            redirect('users');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('users/create');
                return;
            }

            $status = $_POST['status'] ?? 'Active';
            $validStatuses = ['Active', 'Inactive', 'Suspended', 'Locked'];
            if (!in_array($status, $validStatuses)) {
                $status = 'Active';
            }

            $data = [
                'employee_id' => generateEmployeeId(),
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'mobile' => trim($_POST['mobile'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0),
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['username']) || $data['role_id'] === 0) {
                flash('error', 'Required fields are missing');
                redirect('users/create');
                return;
            }

            $existing = $this->db->fetch("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [$data['email']]);
            if ($existing) {
                flash('error', 'Email already exists');
                redirect('users/create');
                return;
            }

            $plainPassword = bin2hex(random_bytes(8));
            $data['password'] = password_hash($plainPassword, PASSWORD_BCRYPT);

            $userId = $this->userModel->create($data);

            if ($userId) {
                logActivity('Create User', 'Users', $userId, null, json_encode($data));

                $emailService = new EmailService();
                $fullName = $data['first_name'] . ' ' . $data['last_name'];
                $emailService->sendNewUserCredentials($data['email'], $fullName, $data['username'], $plainPassword);

                flash('success', 'User created successfully. Credentials sent to email.');
            } else {
                flash('error', 'Failed to create user');
            }

            redirect('users');

        } catch (\Throwable $e) {
            logError('User store error', $e);
            flash('error', 'Failed to create user');
            redirect('users/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $user = $this->userModel->getById($id);
            if (!$user) {
                flash('error', 'User not found');
                redirect('users');
                return;
            }

            $roles = $this->roleModel->getAll();
            $departments = $this->departmentModel->getAll();

            $pageTitle = 'Edit User';
            $content = __DIR__ . '/../views/users/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('User edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('users');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('users/edit/' . $id);
                return;
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                flash('error', 'User not found');
                redirect('users');
                return;
            }

            $status = $_POST['status'] ?? 'Active';
            $validStatuses = ['Active', 'Inactive', 'Suspended', 'Locked'];
            if (!in_array($status, $validStatuses)) {
                $status = 'Active';
            }

            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'mobile' => trim($_POST['mobile'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0),
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'status' => $status,
            ];

            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || $data['role_id'] === 0) {
                flash('error', 'Required fields are missing');
                redirect('users/edit/' . $id);
                return;
            }

            $existing = $this->db->fetch("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL", [$data['email'], $id]);
            if ($existing) {
                flash('error', 'Email already in use');
                redirect('users/edit/' . $id);
                return;
            }

            $this->userModel->update($id, $data);
            logActivity('Update User', 'Users', $id, null, json_encode($data));

            flash('success', 'User updated successfully');
            redirect('users');

        } catch (\Throwable $e) {
            logError('User update error', $e);
            flash('error', 'Failed to update user');
            redirect('users/edit/' . $id);
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            $currentUser = $this->session->get('user');
            if ($currentUser->id === $id) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                return;
            }

            $this->userModel->delete($id);
            logActivity('Delete User', 'Users', $id);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);

        } catch (\Throwable $e) {
            logError('User delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    }

    public function view(int $id): void
    {
        try {
            $user = $this->userModel->getById($id);
            if (!$user) {
                flash('error', 'User not found');
                redirect('users');
                return;
            }

            $taskStats = $this->db->fetch(
                "SELECT COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) as pending_tasks,
                        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks
                 FROM tasks WHERE assigned_to = ? AND deleted_at IS NULL",
                [$id]
            );

            $recentTasks = $this->db->fetchAll(
                "SELECT t.* FROM tasks t WHERE t.assigned_to = ? AND t.deleted_at IS NULL ORDER BY t.created_at DESC LIMIT 10",
                [$id]
            );

            $recentActivity = $this->db->fetchAll(
                "SELECT al.* FROM activity_logs al WHERE al.user_id = ? ORDER BY al.created_at DESC LIMIT 10",
                [$id]
            );

            $pageTitle = 'View User';
            $content = __DIR__ . '/../views/users/view.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('User view error', $e);
            flash('error', 'Unable to load user');
            redirect('users');
        }
    }

    public function resetPassword(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('users/view/' . $id);
                return;
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                flash('error', 'User not found');
                redirect('users');
                return;
            }

            $newPassword = bin2hex(random_bytes(8));
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->userModel->resetPassword($id, $hashed);
            logActivity('Admin Reset Password', 'Users', $id);

            $emailService = new EmailService();
            $fullName = $user->first_name . ' ' . $user->last_name;
            $emailService->sendNewUserCredentials($user->email, $fullName, $user->username, $newPassword);

            flash('success', 'Password reset successfully. New credentials sent to user email.');
            redirect('users/view/' . $id);

        } catch (\Throwable $e) {
            logError('Admin reset password error', $e);
            flash('error', 'Failed to reset password');
            redirect('users/view/' . $id);
        }
    }

    public function updateStatus(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['active', 'inactive', 'suspended', 'locked'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $this->userModel->updateStatus($id, $status);
            logActivity('Update Status', 'Users', $id, $user->status, $status);

            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);

        } catch (\Throwable $e) {
            logError('User status update error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    }

    public function unlock(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('users');
                return;
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                flash('error', 'User not found');
                redirect('users');
                return;
            }

            $this->userModel->updateStatus($id, 'active');
            $this->db->update('users', ['failed_attempts' => 0], 'id = ?', [$id]);
            logActivity('Unlock User', 'Users', $id);

            flash('success', 'User unlocked successfully');
            redirect('users');

        } catch (\Throwable $e) {
            logError('User unlock error', $e);
            flash('error', 'Failed to unlock user');
            redirect('users');
        }
    }
}
