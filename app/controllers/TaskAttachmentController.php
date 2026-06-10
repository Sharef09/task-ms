<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\TaskAttachment;
use App\Models\Task;

class TaskAttachmentController
{
    private Database $db;
    private Session $session;
    private TaskAttachment $attachmentModel;
    private Task $taskModel;

    private array $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv',
        'application/zip',
        'application/json', 'application/xml',
    ];

    private array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'json', 'xml',
    ];

    private int $maxFileSize = 10485760;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->attachmentModel = new TaskAttachment();
        $this->taskModel = new Task();

        if (!$this->session->has('user')) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
                exit;
            }
            redirect('login');
        }
    }

    public function upload(): void
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                if ($isAjax) {
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

            if ($taskId === 0) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                } else {
                    flash('error', 'Task ID is required');
                    redirect('tasks');
                }
                return;
            }

            $task = $this->taskModel->getById($taskId);
            if (!$task) {
                if ($isAjax) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Task not found']);
                } else {
                    flash('error', 'Task not found');
                    redirect('tasks');
                }
                return;
            }

            if (empty($_FILES['file'])) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                } else {
                    flash('error', 'No file uploaded');
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            $file = $_FILES['file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $msg = 'Upload failed with error code: ' . $file['error'];
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    flash('error', $msg);
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            if ($file['size'] > $this->maxFileSize) {
                $maxMb = $this->maxFileSize / 1048576;
                $msg = "File exceeds maximum size of {$maxMb}MB";
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    flash('error', $msg);
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions)) {
                $msg = 'File type not allowed';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    flash('error', $msg);
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedMimes)) {
                $msg = 'File MIME type not allowed';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    flash('error', $msg);
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            $uuid = generateUuid();
            $storedName = $uuid . '.' . $extension;

            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/tasks/' . $taskId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $destPath = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $msg = 'Failed to save file';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    flash('error', $msg);
                    redirect('tasks/view/' . $taskId);
                }
                return;
            }

            $attachmentId = $this->attachmentModel->create([
                'task_id' => $taskId,
                'user_id' => $user->id,
                'original_name' => $file['name'],
                'stored_name' => $storedName,
                'file_path' => 'storage/uploads/tasks/' . $taskId . '/' . $storedName,
                'file_size' => $file['size'],
                'mime_type' => $mimeType,
                'extension' => $extension,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($attachmentId) {
                logActivity('Upload Attachment', 'Tasks', $taskId, null, json_encode([
                    'attachment_id' => $attachmentId,
                    'file_name' => $file['name'],
                ]));

                if ($isAjax) {
                    $attachment = $this->attachmentModel->getById($attachmentId);
                    echo json_encode(['success' => true, 'data' => $attachment]);
                } else {
                    flash('success', 'File uploaded successfully');
                    redirect('tasks/view/' . $taskId);
                }
            } else {
                unlink($destPath);
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to save attachment record']);
                } else {
                    flash('error', 'Failed to save attachment record');
                    redirect('tasks/view/' . $taskId);
                }
            }

        } catch (\Throwable $e) {
            logError('Upload attachment error', $e);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            } else {
                flash('error', 'Failed to upload file');
                redirect('tasks');
            }
        }
    }

    public function download(int $id): void
    {
        try {
            $attachment = $this->attachmentModel->getById($id);
            if (!$attachment) {
                http_response_code(404);
                echo 'File not found';
                return;
            }

            $filePath = dirname(__DIR__, 2) . '/' . $attachment->file_path;

            if (!file_exists($filePath)) {
                logError('Download attachment - file not found: ' . $filePath);
                http_response_code(404);
                echo 'File not found';
                return;
            }

            logActivity('Download Attachment', 'Tasks', $attachment->task_id, null, json_encode([
                'attachment_id' => $id,
                'file_name' => $attachment->original_name,
            ]));

            header('Content-Type: ' . $attachment->mime_type);
            header('Content-Disposition: attachment; filename="' . $attachment->original_name . '"');
            header('Content-Length: ' . $attachment->file_size);
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            readfile($filePath);
            exit;

        } catch (\Throwable $e) {
            logError('Download attachment error', $e);
            http_response_code(500);
            echo 'Failed to download file';
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

            $attachment = $this->attachmentModel->getById($id);
            if (!$attachment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Attachment not found']);
                return;
            }

            $user = $this->session->get('user');

            if ($attachment->uploaded_by !== $user->id && !isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You cannot delete this attachment']);
                return;
            }

            $filePath = dirname(__DIR__, 2) . '/' . $attachment->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->attachmentModel->delete($id);

            logActivity('Delete Attachment', 'Tasks', $attachment->task_id, null, json_encode([
                'attachment_id' => $id,
                'file_name' => $attachment->original_name,
            ]));

            echo json_encode(['success' => true, 'message' => 'Attachment deleted']);

        } catch (\Throwable $e) {
            logError('Delete attachment error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete attachment']);
        }
    }
}
