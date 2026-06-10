<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\UserFile;

class UserFileController
{
    private Database $db;
    private Session $session;
    private UserFile $fileModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->fileModel = new UserFile();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $currentUser = $this->session->get('user');
            $files = $this->fileModel->getByUser($currentUser->id);
            $pageTitle = 'My Files';
            $content = __DIR__ . '/../views/user-files/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('UserFile index error', $e);
            flash('error', 'Unable to load files');
            redirect('dashboard');
        }
    }

    public function upload(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('user-files');
                return;
            }
            $currentUser = $this->session->get('user');
            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                flash('error', 'No file uploaded or upload error');
                redirect('user-files');
                return;
            }
            $file = $_FILES['file'];
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'txt'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                flash('error', 'File type not allowed');
                redirect('user-files');
                return;
            }
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                flash('error', 'File size exceeds 10MB limit');
                redirect('user-files');
                return;
            }
            $uploadDir = dirname(__DIR__, 2) . '/public/storage/uploads/user-files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $storedName = 'file_' . $currentUser->id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destPath = $uploadDir . $storedName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                flash('error', 'Failed to save file');
                redirect('user-files');
                return;
            }
            $folder = trim($_POST['folder'] ?? '/');
            $this->fileModel->create([
                'user_id' => $currentUser->id,
                'original_name' => $file['name'],
                'stored_name' => $storedName,
                'file_path' => 'public/storage/uploads/user-files/' . $storedName,
                'mime_type' => $file['type'],
                'file_size' => $file['size'],
                'folder' => $folder,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            logActivity('Upload File', 'User Files', null, null, json_encode(['name' => $file['name']]));
            flash('success', 'File uploaded successfully');
            redirect('user-files');
        } catch (\Throwable $e) {
            logError('UserFile upload error', $e);
            flash('error', 'Failed to upload file');
            redirect('user-files');
        }
    }

    public function download(int $id): void
    {
        try {
            $currentUser = $this->session->get('user');
            $file = $this->fileModel->getById($id);
            if (!$file) {
                flash('error', 'File not found');
                redirect('user-files');
                return;
            }
            if ($file->user_id != $currentUser->id && !isAdmin()) {
                flash('error', 'You do not have permission to download this file');
                redirect('user-files');
                return;
            }
            $filePath = dirname(__DIR__, 2) . '/' . $file->file_path;
            if (!file_exists($filePath)) {
                flash('error', 'File not found on disk');
                redirect('user-files');
                return;
            }
            header('Content-Type: ' . ($file->mime_type ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . $file->original_name . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } catch (\Throwable $e) {
            logError('UserFile download error', $e);
            flash('error', 'Failed to download file');
            redirect('user-files');
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
            $currentUser = $this->session->get('user');
            $file = $this->fileModel->getById($id);
            if (!$file) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'File not found']);
                return;
            }
            if ($file->user_id != $currentUser->id && !isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                return;
            }
            $filePath = dirname(__DIR__, 2) . '/' . $file->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->fileModel->delete($id);
            logActivity('Delete File', 'User Files', $id, null, json_encode(['name' => $file->original_name]));
            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } catch (\Throwable $e) {
            logError('UserFile delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
        }
    }
}
