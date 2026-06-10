<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\TaskCategory;

class CategoryController
{
    private Database $db;
    private Session $session;
    private TaskCategory $categoryModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->categoryModel = new TaskCategory();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $categories = $this->categoryModel->getAll();
            $pageTitle = 'Task Categories';
            $content = __DIR__ . '/../views/categories/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Category index error', $e);
            flash('error', 'Unable to load categories');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $pageTitle = 'Create Category';
            $content = __DIR__ . '/../views/categories/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Category create form error', $e);
            flash('error', 'Unable to load form');
            redirect('categories');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('categories/create');
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color'] ?? '#2563eb');
            if (empty($name)) {
                flash('error', 'Category name is required');
                redirect('categories/create');
                return;
            }
            $existing = $this->db->fetch("SELECT id FROM task_categories WHERE name = ? AND deleted_at IS NULL", [$name]);
            if ($existing) {
                flash('error', 'Category with this name already exists');
                redirect('categories/create');
                return;
            }
            $catId = $this->categoryModel->create([
                'name' => $name,
                'description' => $description,
                'color' => $color,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($catId) {
                logActivity('Create Category', 'Categories', $catId, null, json_encode(['name' => $name]));
                flash('success', 'Category created successfully');
            } else {
                flash('error', 'Failed to create category');
            }
            redirect('categories');
        } catch (\Throwable $e) {
            logError('Category store error', $e);
            flash('error', 'Failed to create category');
            redirect('categories/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                flash('error', 'Category not found');
                redirect('categories');
                return;
            }
            $pageTitle = 'Edit Category';
            $content = __DIR__ . '/../views/categories/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Category edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('categories');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('categories/edit/' . $id);
                return;
            }
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                flash('error', 'Category not found');
                redirect('categories');
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color'] ?? '#2563eb');
            if (empty($name)) {
                flash('error', 'Category name is required');
                redirect('categories/edit/' . $id);
                return;
            }
            $existing = $this->db->fetch("SELECT id FROM task_categories WHERE name = ? AND id != ? AND deleted_at IS NULL", [$name, $id]);
            if ($existing) {
                flash('error', 'Another category with this name already exists');
                redirect('categories/edit/' . $id);
                return;
            }
            $this->categoryModel->update($id, [
                'name' => $name,
                'description' => $description,
                'color' => $color,
            ]);
            logActivity('Update Category', 'Categories', $id, null, json_encode(['name' => $name]));
            flash('success', 'Category updated successfully');
            redirect('categories');
        } catch (\Throwable $e) {
            logError('Category update error', $e);
            flash('error', 'Failed to update category');
            redirect('categories/edit/' . $id);
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
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                return;
            }
            $taskCount = $this->db->fetch("SELECT COUNT(*) as total FROM tasks WHERE category_id = ? AND deleted_at IS NULL", [$id]);
            if ($taskCount && $taskCount->total > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete category with assigned tasks']);
                return;
            }
            $this->categoryModel->delete($id);
            logActivity('Delete Category', 'Categories', $id);
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } catch (\Throwable $e) {
            logError('Category delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
    }
}
