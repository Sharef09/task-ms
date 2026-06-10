<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Services\BackupService;

class BackupController
{
    private Database $db;
    private Session $session;
    private BackupService $backupService;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->backupService = new BackupService();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;

            $backups = $this->backupService->getHistory($page, $perPage);

            $pageTitle = 'Backups';
            $content = __DIR__ . '/../views/backup/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Backup index error', $e);
            flash('error', 'Unable to load backups');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('backups');
                return;
            }

            $user = $this->session->get('user');
            $result = $this->backupService->createBackup($user->id, 'manual');

            if ($result['success']) {
                flash('success', 'Backup created successfully (' . $result['file'] . ')');
            } else {
                flash('error', $result['message'] ?? 'Failed to create backup');
            }

            redirect('backups');

        } catch (\Throwable $e) {
            logError('Backup create error', $e);
            flash('error', 'Failed to create backup');
            redirect('backups');
        }
    }

    public function restore(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('backups');
                return;
            }

            $result = $this->backupService->restoreBackup($id);

            if ($result['success']) {
                flash('success', 'Database restored successfully');
            } else {
                flash('error', $result['message'] ?? 'Failed to restore backup');
            }

            redirect('backups');

        } catch (\Throwable $e) {
            logError('Backup restore error', $e);
            flash('error', 'Failed to restore backup');
            redirect('backups');
        }
    }

    public function download(int $id): void
    {
        try {
            $filePath = $this->backupService->downloadBackup($id);

            if ($filePath === null) {
                flash('error', 'Backup file not found');
                redirect('backups');
            }

        } catch (\Throwable $e) {
            logError('Backup download error', $e);
            flash('error', 'Failed to download backup');
            redirect('backups');
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('backups');
                return;
            }

            $success = $this->backupService->deleteBackup($id);

            if ($success) {
                logActivity('Delete Backup', 'Database', $id);
                flash('success', 'Backup deleted successfully');
            } else {
                flash('error', 'Failed to delete backup');
            }

            redirect('backups');

        } catch (\Throwable $e) {
            logError('Backup delete error', $e);
            flash('error', 'Failed to delete backup');
            redirect('backups');
        }
    }
}
