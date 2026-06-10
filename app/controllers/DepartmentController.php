<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Department;

class DepartmentController
{
    private Database $db;
    private Session $session;
    private Department $departmentModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->departmentModel = new Department();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $departments = $this->departmentModel->getAll();
            foreach ($departments as $dept) {
                $dept->user_count = $this->departmentModel->getUsersCount($dept->id);
            }
            $pageTitle = 'Departments';
            $content = __DIR__ . '/../views/departments/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Department index error', $e);
            flash('error', 'Unable to load departments');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $pageTitle = 'Create Department';
            $content = __DIR__ . '/../views/departments/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Department create form error', $e);
            flash('error', 'Unable to load form');
            redirect('departments');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('departments/create');
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (empty($name)) {
                flash('error', 'Department name is required');
                redirect('departments/create');
                return;
            }
            $existing = $this->db->fetch("SELECT id FROM departments WHERE name = ? AND deleted_at IS NULL", [$name]);
            if ($existing) {
                flash('error', 'Department with this name already exists');
                redirect('departments/create');
                return;
            }
            $deptId = $this->departmentModel->create([
                'name' => $name,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($deptId) {
                logActivity('Create Department', 'Departments', $deptId, null, json_encode(['name' => $name]));
                flash('success', 'Department created successfully');
            } else {
                flash('error', 'Failed to create department');
            }
            redirect('departments');
        } catch (\Throwable $e) {
            logError('Department store error', $e);
            flash('error', 'Failed to create department');
            redirect('departments/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $department = $this->departmentModel->getById($id);
            if (!$department) {
                flash('error', 'Department not found');
                redirect('departments');
                return;
            }
            $pageTitle = 'Edit Department';
            $content = __DIR__ . '/../views/departments/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Department edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('departments');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('departments/edit/' . $id);
                return;
            }
            $department = $this->departmentModel->getById($id);
            if (!$department) {
                flash('error', 'Department not found');
                redirect('departments');
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (empty($name)) {
                flash('error', 'Department name is required');
                redirect('departments/edit/' . $id);
                return;
            }
            $existing = $this->db->fetch("SELECT id FROM departments WHERE name = ? AND id != ? AND deleted_at IS NULL", [$name, $id]);
            if ($existing) {
                flash('error', 'Another department with this name already exists');
                redirect('departments/edit/' . $id);
                return;
            }
            $this->departmentModel->update($id, [
                'name' => $name,
                'description' => $description,
            ]);
            logActivity('Update Department', 'Departments', $id, null, json_encode(['name' => $name]));
            flash('success', 'Department updated successfully');
            redirect('departments');
        } catch (\Throwable $e) {
            logError('Department update error', $e);
            flash('error', 'Failed to update department');
            redirect('departments/edit/' . $id);
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
            $department = $this->departmentModel->getById($id);
            if (!$department) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Department not found']);
                return;
            }
            $userCount = $this->departmentModel->getUsersCount($id);
            if ($userCount > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete department with assigned users']);
                return;
            }
            $this->departmentModel->delete($id);
            logActivity('Delete Department', 'Departments', $id);
            echo json_encode(['success' => true, 'message' => 'Department deleted successfully']);
        } catch (\Throwable $e) {
            logError('Department delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete department']);
        }
    }
}
