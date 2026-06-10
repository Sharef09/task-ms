<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\EmployeeIdea;

class EmployeeIdeaController
{
    private Database $db;
    private Session $session;
    private EmployeeIdea $ideaModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->ideaModel = new EmployeeIdea();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $currentUser = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
            ];
            if (!isAdmin()) {
                $filters['submitted_by'] = $currentUser->id;
            }
            $result = $this->ideaModel->getAll($page, $perPage, $filters);
            $ideas = $result['data'];
            $total = $result['total'];
            $totalPages = ceil($total / $perPage);
            $pageTitle = 'Employee Ideas';
            $content = __DIR__ . '/../views/employee-ideas/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('EmployeeIdea index error', $e);
            flash('error', 'Unable to load ideas');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $pageTitle = 'Submit Idea';
            $content = __DIR__ . '/../views/employee-ideas/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('EmployeeIdea create form error', $e);
            flash('error', 'Unable to load form');
            redirect('employee-ideas');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('employee-ideas/create');
                return;
            }
            $currentUser = $this->session->get('user');
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = $_POST['category'] ?? 'Improvement';
            $validCategories = ['Improvement', 'Innovation', 'Efficiency', 'Cost Saving', 'Safety', 'Other'];
            if (!in_array($category, $validCategories)) {
                $category = 'Improvement';
            }
            if (empty($title)) {
                flash('error', 'Idea title is required');
                redirect('employee-ideas/create');
                return;
            }
            $data = [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'status' => 'Submitted',
                'submitted_by' => $currentUser->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $ideaId = $this->ideaModel->create($data);
            if ($ideaId) {
                logActivity('Submit Idea', 'Employee Ideas', $ideaId, null, json_encode(['title' => $title]));
                flash('success', 'Idea submitted successfully');
            } else {
                flash('error', 'Failed to submit idea');
            }
            redirect('employee-ideas');
        } catch (\Throwable $e) {
            logError('EmployeeIdea store error', $e);
            flash('error', 'Failed to submit idea');
            redirect('employee-ideas/create');
        }
    }

    public function view(int $id): void
    {
        try {
            $idea = $this->ideaModel->getById($id);
            if (!$idea) {
                flash('error', 'Idea not found');
                redirect('employee-ideas');
                return;
            }
            $pageTitle = 'View Idea';
            $content = __DIR__ . '/../views/employee-ideas/view.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('EmployeeIdea view error', $e);
            flash('error', 'Unable to load idea');
            redirect('employee-ideas');
        }
    }

    public function edit(int $id): void
    {
        try {
            $idea = $this->ideaModel->getById($id);
            if (!$idea) {
                flash('error', 'Idea not found');
                redirect('employee-ideas');
                return;
            }
            $currentUser = $this->session->get('user');
            if ($idea->submitted_by != $currentUser->id && !isAdmin()) {
                flash('error', 'You do not have permission to edit this idea');
                redirect('employee-ideas');
                return;
            }
            if ($idea->status !== 'Submitted') {
                flash('error', 'Cannot edit an idea that has been reviewed');
                redirect('employee-ideas/view/' . $id);
                return;
            }
            $pageTitle = 'Edit Idea';
            $content = __DIR__ . '/../views/employee-ideas/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('EmployeeIdea edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('employee-ideas');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('employee-ideas/edit/' . $id);
                return;
            }
            $idea = $this->ideaModel->getById($id);
            if (!$idea) {
                flash('error', 'Idea not found');
                redirect('employee-ideas');
                return;
            }
            $currentUser = $this->session->get('user');
            if ($idea->submitted_by != $currentUser->id && !isAdmin()) {
                flash('error', 'You do not have permission to edit this idea');
                redirect('employee-ideas');
                return;
            }
            if ($idea->status !== 'Submitted') {
                flash('error', 'Cannot edit an idea that has been reviewed');
                redirect('employee-ideas/view/' . $id);
                return;
            }
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = $_POST['category'] ?? 'Improvement';
            $validCategories = ['Improvement', 'Innovation', 'Efficiency', 'Cost Saving', 'Safety', 'Other'];
            if (!in_array($category, $validCategories)) {
                $category = 'Improvement';
            }
            if (empty($title)) {
                flash('error', 'Idea title is required');
                redirect('employee-ideas/edit/' . $id);
                return;
            }
            $this->ideaModel->update($id, [
                'title' => $title,
                'description' => $description,
                'category' => $category,
            ]);
            logActivity('Update Idea', 'Employee Ideas', $id, null, json_encode(['title' => $title]));
            flash('success', 'Idea updated successfully');
            redirect('employee-ideas/view/' . $id);
        } catch (\Throwable $e) {
            logError('EmployeeIdea update error', $e);
            flash('error', 'Failed to update idea');
            redirect('employee-ideas/edit/' . $id);
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
            $idea = $this->ideaModel->getById($id);
            if (!$idea) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Idea not found']);
                return;
            }
            $this->ideaModel->delete($id);
            logActivity('Delete Idea', 'Employee Ideas', $id);
            echo json_encode(['success' => true, 'message' => 'Idea deleted successfully']);
        } catch (\Throwable $e) {
            logError('EmployeeIdea delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete idea']);
        }
    }

    public function review(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('employee-ideas');
                return;
            }
            $idea = $this->ideaModel->getById($id);
            if (!$idea) {
                flash('error', 'Idea not found');
                redirect('employee-ideas');
                return;
            }
            $currentUser = $this->session->get('user');
            $status = $_POST['status'] ?? '';
            $validStatuses = ['Under Review', 'Approved', 'Implemented', 'Rejected'];
            if (!in_array($status, $validStatuses)) {
                flash('error', 'Invalid status');
                redirect('employee-ideas/view/' . $id);
                return;
            }
            $reviewNotes = trim($_POST['review_notes'] ?? '');
            $estimatedSavings = !empty($_POST['estimated_savings']) ? (float)$_POST['estimated_savings'] : null;
            $this->ideaModel->update($id, [
                'status' => $status,
                'reviewed_by' => $currentUser->id,
                'review_notes' => $reviewNotes,
                'estimated_savings' => $estimatedSavings,
            ]);
            logActivity('Review Idea', 'Employee Ideas', $id, $idea->status, $status);
            flash('success', 'Idea review updated successfully');
            redirect('employee-ideas/view/' . $id);
        } catch (\Throwable $e) {
            logError('EmployeeIdea review error', $e);
            flash('error', 'Failed to review idea');
            redirect('employee-ideas/view/' . $id);
        }
    }
}
