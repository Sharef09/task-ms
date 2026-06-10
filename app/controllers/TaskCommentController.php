<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\TaskComment;
use App\Models\Task;
use App\Services\NotificationService;

class TaskCommentController
{
    private Database $db;
    private Session $session;
    private TaskComment $commentModel;
    private Task $taskModel;
    private NotificationService $notificationService;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->commentModel = new TaskComment();
        $this->taskModel = new Task();
        $this->notificationService = new NotificationService();

        if (!$this->session->has('user')) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
                exit;
            }
            redirect('login');
        }
    }

    public function store(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                } else {
                    flash('error', 'Invalid security token');
                    redirect('tasks');
                }
                return;
            }

            $user = $this->session->get('user');
            $taskId = (int) ($_POST['task_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $isInternal = isset($_POST['is_internal']) && (int)$_POST['is_internal'] === 1;

            if ($taskId === 0 || empty($content)) {
                flash('error', 'Missing required fields');
                redirect('tasks/view/' . $taskId);
                return;
            }

            $task = $this->taskModel->getById($taskId);
            if (!$task) {
                flash('error', 'Task not found');
                redirect('tasks');
                return;
            }

            $attachmentPath = null;
            if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/public/storage/uploads/comments/' . $taskId;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                $storedName = uniqid('comment_') . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . '/' . $storedName)) {
                    $attachmentPath = 'storage/uploads/comments/' . $taskId . '/' . $storedName;
                }
            }

            $commentId = $this->commentModel->create([
                'task_id' => $taskId,
                'user_id' => $user->id,
                'comment' => $content,
                'is_internal' => $isInternal ? 1 : 0,
                'attachment' => $attachmentPath,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($commentId) {
                logActivity('Add Comment', 'Tasks', $taskId, null, json_encode([
                    'comment_id' => $commentId,
                    'is_internal' => $isInternal,
                ]));

                preg_match_all('/@(\w+)/', $content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $username) {
                        $mentioned = $this->db->fetch(
                            "SELECT id FROM users WHERE username = ? AND deleted_at IS NULL",
                            [$username]
                        );
                        if ($mentioned && $mentioned->id !== $user->id) {
                            $this->notificationService->sendMentionNotification(
                                $mentioned->id,
                                $user->first_name . ' ' . $user->last_name,
                                $task->task_number,
                                $content,
                                $taskId
                            );
                        }
                    }
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    $comment = $this->db->fetch(
                        "SELECT tc.*, u.first_name, u.last_name, u.avatar
                         FROM task_comments tc
                         LEFT JOIN users u ON u.id = tc.user_id
                         WHERE tc.id = ?",
                        [$commentId]
                    );
                    echo json_encode(['success' => true, 'data' => $comment]);
                } else {
                    flash('success', 'Comment added successfully');
                    redirect('tasks/view/' . $taskId);
                }
            } else {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
                } else {
                    flash('error', 'Failed to add comment');
                    redirect('tasks/view/' . $taskId);
                }
            }

        } catch (\Throwable $e) {
            logError('Add comment error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
    }

    public function delete(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->session->get('user');
            $comment = $this->db->fetch(
                "SELECT * FROM task_comments WHERE id = ?",
                [$id]
            );

            if (!$comment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
                return;
            }

            if ($comment->user_id !== $user->id && !isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You cannot delete this comment']);
                return;
            }

            $this->commentModel->delete($id);

            logActivity('Delete Comment', 'Tasks', $comment->task_id, null, json_encode(['comment_id' => $id]));

            echo json_encode(['success' => true, 'message' => 'Comment deleted']);

        } catch (\Throwable $e) {
            logError('Delete comment error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
    }
}
