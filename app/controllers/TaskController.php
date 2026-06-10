<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskCategory;
use App\Models\Department;
use App\Models\TaskHistory;
use App\Services\NotificationService;
use App\Models\TaskAssignment;
use App\Models\TaskDependency;
use App\Models\TaskTemplate;
use App\Models\TaskWatcher;
use App\Models\TaskTag;
use App\Models\UserFile;
use App\Models\TaskMention;

class TaskController
{
    private Database $db;
    private Session $session;
    private Task $taskModel;
    private User $userModel;
    private TaskCategory $categoryModel;
    private Department $departmentModel;
    private TaskHistory $historyModel;
    private NotificationService $notificationService;
    private TaskAssignment $assignmentModel;
    private TaskDependency $dependencyModel;
    private TaskTemplate $templateModel;
    private TaskWatcher $watcherModel;
    private TaskTag $tagModel;
    private TaskMention $mentionModel;
    private UserFile $fileModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->taskModel = new Task();
        $this->userModel = new User();
        $this->categoryModel = new TaskCategory();
        $this->departmentModel = new Department();
        $this->historyModel = new TaskHistory();
        $this->notificationService = new NotificationService();
        $this->assignmentModel = new TaskAssignment();
        $this->dependencyModel = new TaskDependency();
        $this->templateModel = new TaskTemplate();
        $this->watcherModel = new TaskWatcher();
        $this->tagModel = new TaskTag();
        $this->mentionModel = new TaskMention();
        $this->fileModel = new UserFile();

        if (!$this->session->has('user')) {
            redirect('login');
        }

        $this->autoSetOverdue();
    }

    public function index(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'assigned_to' => $_GET['assigned_to'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'task_type' => $_GET['task_type'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            if (isStaff()) {
                $filters['user_id'] = $user->id;
            }

            $tasks = $this->taskModel->getAll($page, $perPage, $filters);
            if (isStaff()) {
                $uid = (int)$user->id;
                $total = $this->db->fetch(
                    "SELECT COUNT(*) as cnt FROM tasks t WHERE t.deleted_at IS NULL AND (t.assigned_to = {$uid} OR t.created_by = {$uid})"
                )->cnt ?? 0;
            } else {
                $total = $this->db->fetch("SELECT COUNT(*) as cnt FROM tasks t WHERE t.deleted_at IS NULL")->cnt ?? 0;
            }
            $totalPages = ceil($total / $perPage);

            $users = $this->userModel->getActive();
            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];
            $statuses = ['Draft', 'Pending', 'In Progress', 'Under Review', 'Completed', 'Rejected', 'Cancelled', 'Overdue'];
            $taskTypes = ['Normal', 'File Attached', 'Initiation'];

            $pageTitle = 'Tasks';
            $content = __DIR__ . '/../views/tasks/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Task index error', $e);
            flash('error', 'Unable to load tasks');
            redirect('dashboard');
        }
    }

    private function restrictStaff(): void
    {
        if (isStaff()) {
            flash('error', "You don't have enough permission, contact the administrator");
            redirect('tasks');
            exit;
        }
    }

    private function saveTaskAttachment(int $taskId, int $userId, array $file): void
    {
        $allowedExts = ['jpg','jpeg','png','gif','webp','svg','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','json','xml'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) return;

        $uploadDir = dirname(__DIR__, 2) . '/public/storage/uploads/tasks/' . $taskId;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $storedName = uniqid('file_', true) . '.' . $ext;
        $destPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) return;

        $this->db->insert('task_attachments', [
            'task_id' => $taskId,
            'user_id' => $userId,
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'file_path' => 'public/storage/uploads/tasks/' . $taskId . '/' . $storedName,
            'file_size' => $file['size'],
            'mime_type' => $file['type'] ?? '',
            'extension' => $ext,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function create(): void
    {
        try {
            $this->restrictStaff();
            $users = $this->userModel->getActive();
            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];

            $pageTitle = 'Create Task';
            $content = __DIR__ . '/../views/tasks/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Task create form error', $e);
            flash('error', 'Unable to load form');
            redirect('tasks');
        }
    }

    public function store(): void
    {
        try {
            $this->restrictStaff();

            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/create');
                return;
            }

            $user = $this->session->get('user');

            $validTypes = ['Normal', 'File Attached', 'Initiation'];
            $taskType = $_POST['task_type'] ?? 'Normal';
            if (!in_array($taskType, $validTypes)) $taskType = 'Normal';

            $data = [
                'task_number' => generateTaskNumber(),
                'title' => trim($_POST['title'] ?? ''),
                'task_type' => $taskType,
                'description' => trim($_POST['description'] ?? ''),
                'priority' => $_POST['priority'] ?? 'Medium',
                'status' => $_POST['status'] ?? 'Open',
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'estimated_hours' => !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null,
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'created_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($data['title'])) {
                flash('error', 'Task title is required');
                redirect('tasks/create');
                return;
            }

            if ($data['assigned_to']) {
                $data['assigned_by'] = $user->id;
                $data['assigned_at'] = date('Y-m-d H:i:s');
                if ($data['status'] === 'Open') {
                    $data['status'] = 'Assigned';
                }
            }

            $taskId = $this->taskModel->create($data);

            if ($taskId) {
                $this->historyModel->create([
                    'task_id' => $taskId,
                    'user_id' => $user->id,
                    'field_changed' => 'created',
                    'old_value' => null,
                    'new_value' => json_encode($data),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                logActivity('Create Task', 'Tasks', $taskId, null, json_encode(['task_number' => $data['task_number']]));

                if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                    $this->saveTaskAttachment($taskId, $user->id, $_FILES['attachment']);
                }

                if ($data['assigned_to']) {
                    try {
                        $assignee = $this->userModel->getById($data['assigned_to']);
                        $this->notificationService->sendTaskAssignmentNotification(
                            $taskId, $data['task_number'], $data['title'],
                            $data['assigned_to'], $user->first_name . ' ' . $user->last_name
                        );
                    } catch (\Throwable $e) {
                        logError('Task notification failed', $e);
                    }
                }

                flash('success', 'Task created successfully');
            } else {
                flash('error', 'Failed to create task');
            }

            redirect('tasks');

        } catch (\Throwable $e) {
            logError('Task store error', $e);
            flash('error', 'Failed to create task');
            redirect('tasks/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $this->restrictStaff();
            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $users = $this->userModel->getActive();
            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];
            $statuses = ['Draft', 'Pending', 'In Progress', 'Under Review', 'Completed', 'Rejected', 'Cancelled', 'Overdue'];

            $pageTitle = 'Edit Task';
            $content = __DIR__ . '/../views/tasks/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Task edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('tasks');
        }
    }

    public function update(int $id): void
    {
        try {
            $this->restrictStaff();

            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/edit/' . $id);
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $validTypes = ['Normal', 'File Attached', 'Initiation'];
            $taskType = $_POST['task_type'] ?? 'Normal';
            if (!in_array($taskType, $validTypes)) $taskType = 'Normal';

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'task_type' => $taskType,
                'description' => trim($_POST['description'] ?? ''),
                'priority' => $_POST['priority'] ?? 'Medium',
                'status' => $_POST['status'] ?? 'Open',
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'estimated_hours' => !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null,
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            ];

            if (empty($data['title'])) {
                flash('error', 'Task title is required');
                redirect('tasks/edit/' . $id);
                return;
            }

            $this->taskModel->update($id, $data);

            if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $this->saveTaskAttachment($id, $user->id, $_FILES['attachment']);
            }

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'updated',
                'old_value' => json_encode($task),
                'new_value' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Update Task', 'Tasks', $id, null, json_encode(['task_number' => $task->task_number]));
            flash('success', 'Task updated successfully');
            redirect('tasks');

        } catch (\Throwable $e) {
            logError('Task update error', $e);
            flash('error', 'Failed to update task');
            redirect('tasks/edit/' . $id);
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            if (!isAdmin()) {
                flash('error', "You don't have enough permission, contact the administrator");
                redirect('tasks');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $this->taskModel->delete($id);
            logActivity('Delete Task', 'Tasks', $id);

            flash('success', 'Task deleted successfully');
            redirect('tasks');

        } catch (\Throwable $e) {
            logError('Task delete error', $e);
            flash('error', 'Failed to delete task');
            redirect('tasks');
        }
    }

    public function view(int $id): void
    {
        try {
            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');
            $this->db->query(
                "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link = ? AND is_read = 0",
                [$user->id, '/tasks/view/' . $id]
            );

            $users = $this->userModel->getActive();
            $history = $this->historyModel->getByTask($id);
            $comments = $this->db->fetchAll(
                "SELECT tc.*, u.first_name, u.last_name, u.avatar
                 FROM task_comments tc
                 LEFT JOIN users u ON u.id = tc.user_id
                 WHERE tc.task_id = ? ORDER BY tc.created_at ASC",
                [$id]
            );
            $attachments = $this->db->fetchAll(
                "SELECT ta.*, u.first_name, u.last_name, 'task_attachment' AS source
                 FROM task_attachments ta
                 LEFT JOIN users u ON u.id = ta.user_id
                 WHERE ta.task_id = ? ORDER BY ta.created_at DESC",
                [$id]
            );

            $userFiles = $this->db->fetchAll(
                "SELECT uf.*, u.first_name, u.last_name, 'user_file' AS source
                 FROM user_files uf
                 LEFT JOIN users u ON u.id = uf.user_id
                 WHERE uf.task_id = ? ORDER BY uf.created_at DESC",
                [$id]
            );

            $seenPaths = [];
            foreach ($attachments as $a) {
                $seenPaths[$a->file_path] = true;
            }
            foreach ($userFiles as $uf) {
                if (!isset($seenPaths[$uf->file_path])) {
                    $attachments[] = $uf;
                    $seenPaths[$uf->file_path] = true;
                }
            }

            if (!empty($task->attachment) && !isset($seenPaths[$task->attachment])) {
                $mainAttachment = (object)[
                    'id' => 0,
                    'task_id' => $task->id,
                    'user_id' => $task->created_by,
                    'original_name' => basename($task->attachment),
                    'stored_name' => basename($task->attachment),
                    'file_path' => $task->attachment,
                    'file_size' => 0,
                    'mime_type' => '',
                    'extension' => pathinfo($task->attachment, PATHINFO_EXTENSION),
                    'created_at' => $task->created_at,
                    'first_name' => $task->created_first_name ?? '',
                    'last_name' => $task->created_last_name ?? '',
                    'source' => 'task_column',
                ];
                array_unshift($attachments, $mainAttachment);
            }

            $pageTitle = 'View Task';
            $content = __DIR__ . '/../views/tasks/view.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Task view error', $e);
            flash('error', 'Unable to load task');
            redirect('tasks');
        }
    }

    public function assign(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');
            $assignedTo = (int)($_POST['assigned_to'] ?? 0);

            if ($assignedTo === 0) {
                flash('error', 'Please select a user to assign');
                redirect('tasks/view/' . $id);
                return;
            }

            $assignee = $this->userModel->getById($assignedTo);
            if (!$assignee) {
                flash('error', 'User not found');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->taskModel->assignTask($id, $assignedTo, $user->id);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'assigned',
                'old_value' => $task->assigned_to ? (string)$task->assigned_to : null,
                'new_value' => (string)$assignedTo,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Assign Task', 'Tasks', $id, null, json_encode(['assigned_to' => $assignedTo]));

            $this->notificationService->sendTaskAssignmentNotification(
                $id, $task->task_number, $task->title,
                $assignedTo, $user->first_name . ' ' . $user->last_name
            );

            flash('success', 'Task assigned successfully');
            redirect('tasks/view/' . $id);

        } catch (\Throwable $e) {
            logError('Task assign error', $e);
            flash('error', 'Failed to assign task');
            redirect('tasks/view/' . $id);
        }
    }

    public function reassign(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');
            $newAssigneeId = (int)($_POST['assigned_to'] ?? 0);

            if ($newAssigneeId === 0) {
                flash('error', 'Please select a user');
                redirect('tasks/view/' . $id);
                return;
            }

            $oldAssigneeId = $task->assigned_to;

            $this->taskModel->reassignTask($id, $newAssigneeId, $oldAssigneeId, $user->id);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'reassigned',
                'old_value' => (string)$oldAssigneeId,
                'new_value' => (string)$newAssigneeId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Reassign Task', 'Tasks', $id, null, json_encode([
                'from' => $oldAssigneeId,
                'to' => $newAssigneeId,
            ]));

            $oldUser = $oldAssigneeId ? $this->userModel->getById($oldAssigneeId) : null;
            $oldName = $oldUser ? $oldUser->first_name . ' ' . $oldUser->last_name : 'Unassigned';

            $this->notificationService->sendTaskReassignmentNotification(
                $id, $task->task_number, $task->title,
                $newAssigneeId, $oldName, $user->first_name . ' ' . $user->last_name
            );

            flash('success', 'Task reassigned successfully');
            redirect('tasks/view/' . $id);

        } catch (\Throwable $e) {
            logError('Task reassign error', $e);
            flash('error', 'Failed to reassign task');
            redirect('tasks/view/' . $id);
        }
    }

    public function complete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $this->taskModel->update($id, [
                'status' => 'Completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'actual_hours' => !empty($_POST['actual_hours']) ? (float)$_POST['actual_hours'] : null,
            ]);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => $task->status,
                'new_value' => 'Completed',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Complete Task', 'Tasks', $id);

            if ($task->assigned_to) {
                $this->notificationService->sendTaskCompletedNotification(
                    $id, $task->task_number, $task->title,
                    $task->created_by, $user->first_name . ' ' . $user->last_name
                );
            }

            flash('success', 'Task marked as completed');
            redirect('tasks/view/' . $id);

        } catch (\Throwable $e) {
            logError('Task complete error', $e);
            flash('error', 'Failed to complete task');
            redirect('tasks');
        }
    }

    public function reopen(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $this->taskModel->update($id, [
                'status' => 'Open',
                'completed_at' => null,
            ]);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => $task->status,
                'new_value' => 'Open',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Reopen Task', 'Tasks', $id);

            flash('success', 'Task reopened');
            redirect('tasks/view/' . $id);

        } catch (\Throwable $e) {
            logError('Task reopen error', $e);
            flash('error', 'Failed to reopen task');
            redirect('tasks');
        }
    }

    public function archive(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $this->taskModel->update($id, ['archived' => 1]);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'archived',
                'old_value' => $task->archived ?? 0,
                'new_value' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Archive Task', 'Tasks', $id);

            flash('success', 'Task archived');
            redirect('tasks');

        } catch (\Throwable $e) {
            logError('Task archive error', $e);
            flash('error', 'Failed to archive task');
            redirect('tasks');
        }
    }

    public function clone(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            $original = $this->taskModel->getById($id);
            if (!$original) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $data = [
                'task_number' => generateTaskNumber(),
                'title' => $original->title . ' (Clone)',
                'description' => $original->description,
                'priority' => $original->priority,
                'status' => 'Open',
                'department_id' => $original->department_id,
                'category_id' => $original->category_id,
                'estimated_hours' => $original->estimated_hours,
                'due_date' => $original->due_date,
                'created_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($original->assigned_to) {
                $data['assigned_to'] = $original->assigned_to;
                $data['assigned_by'] = $user->id;
                $data['assigned_at'] = date('Y-m-d H:i:s');
            }

            $newId = $this->taskModel->create($data);

            if ($newId) {
                logActivity('Clone Task', 'Tasks', $newId, null, json_encode(['source_task_id' => $id]));
                flash('success', 'Task cloned successfully');
            } else {
                flash('error', 'Failed to clone task');
            }

            redirect('tasks');

        } catch (\Throwable $e) {
            logError('Task clone error', $e);
            flash('error', 'Failed to clone task');
            redirect('tasks');
        }
    }

    public function bulkAction(): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $action = $_POST['bulk_action'] ?? '';
            $taskIds = array_map('intval', $_POST['task_ids'] ?? []);

            if (empty($action) || empty($taskIds)) {
                echo json_encode(['success' => false, 'message' => 'No action or tasks selected']);
                return;
            }

            $user = $this->session->get('user');
            $placeholders = implode(',', array_fill(0, count($taskIds), '?'));

            switch ($action) {
                case 'delete':
                    $this->db->query("UPDATE tasks SET deleted_at = NOW() WHERE id IN ($placeholders)", $taskIds);
                    break;
                case 'archive':
                    $this->db->query("UPDATE tasks SET status = 'Archived' WHERE id IN ($placeholders)", $taskIds);
                    break;
                case 'complete':
                    $this->db->query("UPDATE tasks SET status = 'Completed', completed_at = NOW() WHERE id IN ($placeholders)", $taskIds);
                    break;
                case 'reopen':
                    $this->db->query("UPDATE tasks SET status = 'Open', completed_at = NULL WHERE id IN ($placeholders)", $taskIds);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    return;
            }

            logActivity('Bulk ' . ucfirst($action), 'Tasks', null, null, json_encode(['task_ids' => $taskIds]));
            echo json_encode(['success' => true, 'message' => 'Bulk action completed']);

        } catch (\Throwable $e) {
            logError('Bulk action error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to perform bulk action']);
        }
    }

    private function autoSetOverdue(): void
    {
        try {
            $this->db->query(
                "UPDATE tasks SET status = 'Overdue'
                 WHERE due_date < NOW()
                   AND status NOT IN ('Completed', 'Cancelled', 'Overdue', 'Archived')
                   AND deleted_at IS NULL"
            );
        } catch (\Throwable $e) {
            logError('Auto-set overdue failed', $e);
        }
    }

    public function myTasks(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            $tasks = $this->taskModel->getMyTasks($user->id, $page, $perPage, $filters);
            $total = $this->taskModel->getMyTasksCount($user->id, $filters);
            $totalPages = ceil($total / $perPage);

            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];
            $statuses = ['Open', 'Assigned', 'In Progress', 'Waiting', 'On Hold', 'Completed', 'Cancelled', 'Overdue'];

            $pageTitle = 'My Tasks';
            $content = __DIR__ . '/../views/tasks/my-tasks.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('My tasks error', $e);
            flash('error', 'Unable to load my tasks');
            redirect('dashboard');
        }
    }

    public function sentTasks(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            $tasks = $this->taskModel->getSentTasks($user->id, $page, $perPage, $filters);
            $total = $this->taskModel->getSentTasksCount($user->id, $filters);
            $totalPages = ceil($total / $perPage);

            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];
            $statuses = ['Open', 'Assigned', 'In Progress', 'Waiting', 'On Hold', 'Completed', 'Cancelled', 'Overdue'];

            $pageTitle = 'Sent Tasks';
            $content = __DIR__ . '/../views/tasks/sent-tasks.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Sent tasks error', $e);
            flash('error', 'Unable to load sent tasks');
            redirect('dashboard');
        }
    }

    public function abusedTasks(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            $tasks = $this->taskModel->getAbusedTasks($user->id, $page, $perPage, $filters);
            $total = $this->taskModel->getAbusedTasksCount($user->id, $filters);
            $totalPages = ceil($total / $perPage);

            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];
            $statuses = ['Open', 'Assigned', 'In Progress', 'Waiting', 'On Hold', 'Completed', 'Cancelled', 'Overdue'];

            $pageTitle = 'Abused Tasks';
            $content = __DIR__ . '/../views/tasks/abused-tasks.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Abused tasks error', $e);
            flash('error', 'Unable to load abused tasks');
            redirect('dashboard');
        }
    }

    public function workload(): void
    {
        try {
            $workload = $this->taskModel->getWorkloadByUser();
            $deptPerformance = $this->taskModel->getDepartmentPerformance();

            $pageTitle = 'Task Workload';
            $content = __DIR__ . '/../views/tasks/workload.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Workload error', $e);
            flash('error', 'Unable to load workload');
            redirect('dashboard');
        }
    }

    public function myFiles(): void
    {
        try {
            $user = $this->session->get('user');
            $folder = $_GET['folder'] ?? '/';
            $files = $this->fileModel->getByUser($user->id, $folder);

            $pageTitle = 'My Files';
            $content = __DIR__ . '/../views/tasks/my-files.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('My files error', $e);
            flash('error', 'Unable to load files');
            redirect('dashboard');
        }
    }

    public function uploadMyFile(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                } else {
                    flash('error', 'Invalid security token');
                    redirect('tasks/my-files');
                }
                return;
            }

            $user = $this->session->get('user');
            $uploadDir = dirname(__DIR__, 2) . '/public/storage/uploads/user-files/' . $user->id . '/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('File upload failed');
            }

            $originalName = basename($_FILES['file']['name']);
            $storedName = uniqid('file_', true) . '_' . $originalName;
            $filePath = $uploadDir . $storedName;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                throw new \RuntimeException('Failed to move uploaded file');
            }

            $this->fileModel->create([
                'user_id' => $user->id,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_path' => 'storage/uploads/user-files/' . $user->id . '/' . $storedName,
                'file_size' => $_FILES['file']['size'],
                'mime_type' => $_FILES['file']['type'],
                'folder' => $_POST['folder'] ?? '/',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
            } else {
                flash('success', 'File uploaded successfully');
                redirect('tasks/my-files');
            }
        } catch (\Throwable $e) {
            logError('Upload my file error', $e);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            } else {
                flash('error', 'Failed to upload file');
                redirect('tasks/my-files');
            }
        }
    }

    public function deleteMyFile(int $id): void
    {
        try {
            $user = $this->session->get('user');
            $file = $this->fileModel->getById($id);

            if (!$file || $file->user_id !== $user->id) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'File not found']);
                return;
            }

            $filePath = dirname(__DIR__, 2) . '/public/' . $file->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->fileModel->delete($id);

            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } catch (\Throwable $e) {
            logError('Delete my file error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
        }
    }

    public function downloadMyFile(int $id): void
    {
        try {
            $user = $this->session->get('user');
            $file = $this->fileModel->getById($id);

            if (!$file || $file->user_id !== $user->id) {
                flash('error', 'File not found');
                redirect('tasks/my-files');
                return;
            }

            $filePath = dirname(__DIR__, 2) . '/public/' . $file->file_path;
            if (!file_exists($filePath)) {
                flash('error', 'File not found');
                redirect('tasks/my-files');
                return;
            }

            header('Content-Type: ' . ($file->mime_type ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . $file->original_name . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } catch (\Throwable $e) {
            logError('Download my file error', $e);
            flash('error', 'Failed to download file');
            redirect('tasks/my-files');
        }
    }

    public function addDependency(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $dependsOnId = (int)($_POST['depends_on_id'] ?? 0);
            $dependencyType = $_POST['dependency_type'] ?? 'blocks';

            if ($dependsOnId <= 0) {
                flash('error', 'Invalid dependency task');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->dependencyModel->create([
                'task_id' => $id,
                'depends_on_id' => $dependsOnId,
                'dependency_type' => $dependencyType,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            flash('success', 'Dependency added successfully');
            redirect('tasks/view/' . $id);
        } catch (\Throwable $e) {
            logError('Add dependency error', $e);
            flash('error', 'Failed to add dependency');
            redirect('tasks/view/' . $id);
        }
    }

    public function removeDependency(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->dependencyModel->delete($id);
            flash('success', 'Dependency removed successfully');
            redirect('tasks/view/' . $_POST['task_id'] ?? 0);
        } catch (\Throwable $e) {
            logError('Remove dependency error', $e);
            flash('error', 'Failed to remove dependency');
            redirect('tasks');
        }
    }

    public function addWatcher(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                flash('error', 'Invalid user');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->watcherModel->addWatcher($id, $userId);
            flash('success', 'Watcher added successfully');
            redirect('tasks/view/' . $id);
        } catch (\Throwable $e) {
            logError('Add watcher error', $e);
            flash('error', 'Failed to add watcher');
            redirect('tasks/view/' . $id);
        }
    }

    public function removeWatcher(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $user = $this->session->get('user');
            $this->watcherModel->removeWatcher($id, $user->id);
            flash('success', 'Removed from watchers');
            redirect('tasks/view/' . $id);
        } catch (\Throwable $e) {
            logError('Remove watcher error', $e);
            flash('error', 'Failed to remove watcher');
            redirect('tasks/view/' . $id);
        }
    }

    public function inProgress(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $user = $this->session->get('user');

            $this->taskModel->update($id, [
                'status' => 'In Progress',
            ]);

            $this->historyModel->create([
                'task_id' => $id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => $task->status,
                'new_value' => 'In Progress',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Start Task', 'Tasks', $id);

            flash('success', 'Task status set to In Progress');
            redirect('tasks/view/' . $id);

        } catch (\Throwable $e) {
            logError('Task inProgress error', $e);
            flash('error', 'Failed to update task status');
            redirect('tasks');
        }
    }

    public function fromTemplate(int $id): void
    {
        try {
            $this->restrictStaff();
            $template = $this->templateModel->getById($id);
            if (!$template) {
                flash('error', 'Template not found');
                redirect('tasks/templates');
                return;
            }

            $users = $this->userModel->getActive();
            $categories = $this->categoryModel->getAll();
            $departments = $this->departmentModel->getAll();
            $priorities = ['Low', 'Medium', 'High', 'Critical'];

            $pageTitle = 'Create Task from Template';
            $content = __DIR__ . '/../views/tasks/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('From template error', $e);
            flash('error', 'Unable to load template');
            redirect('tasks/templates');
        }
    }

    public function templates(): void
    {
        try {
            $templates = $this->templateModel->getAll();

            $pageTitle = 'Task Templates';
            $content = __DIR__ . '/../views/tasks/templates.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Templates error', $e);
            flash('error', 'Unable to load templates');
            redirect('dashboard');
        }
    }

    public function storeTemplate(): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/templates');
                return;
            }

            $user = $this->session->get('user');

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'priority' => $_POST['priority'] ?? 'Medium',
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'estimated_hours' => !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null,
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'created_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($data['name'])) {
                flash('error', 'Template name is required');
                redirect('tasks/templates');
                return;
            }

            $this->templateModel->create($data);
            flash('success', 'Template saved successfully');
            redirect('tasks/templates');
        } catch (\Throwable $e) {
            logError('Store template error', $e);
            flash('error', 'Failed to save template');
            redirect('tasks/templates');
        }
    }

    public function updateProgress(int $id): void
    {
        try {
            $percentage = max(0, min(100, (int)($_POST['progress_percentage'] ?? 0)));

            $this->taskModel->update($id, ['progress_percentage' => $percentage]);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => true, 'progress_percentage' => $percentage]);
            } else {
                flash('success', 'Progress updated successfully');
                redirect('tasks/view/' . $id);
            }
        } catch (\Throwable $e) {
            logError('Update progress error', $e);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            } else {
                flash('error', 'Failed to update progress');
                redirect('tasks/view/' . $id);
            }
        }
    }

    public function addTag(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $tagName = trim($_POST['tag'] ?? '');
            if (empty($tagName)) {
                flash('error', 'Tag name is required');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->tagModel->addTag($id, $tagName);
            flash('success', 'Tag added successfully');
            redirect('tasks/view/' . $id);
        } catch (\Throwable $e) {
            logError('Add tag error', $e);
            flash('error', 'Failed to add tag');
            redirect('tasks/view/' . $id);
        }
    }

    public function removeTag(int $id): void
    {
        try {
            $this->restrictStaff();
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('tasks/view/' . $id);
                return;
            }

            $this->tagModel->removeTag($id);
            flash('success', 'Tag removed successfully');
            redirect('tasks/view/' . $id);
        } catch (\Throwable $e) {
            logError('Remove tag error', $e);
            flash('error', 'Failed to remove tag');
            redirect('tasks/view/' . $id);
        }
    }
}
