<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogController
{
    private Database $db;
    private Session $session;
    private ActivityLog $logModel;
    private User $userModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->logModel = new ActivityLog();
        $this->userModel = new User();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 25;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'user_id' => $_GET['user_id'] ?? '',
                'action' => $_GET['action'] ?? '',
                'module' => $_GET['module'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            $logs = $this->logModel->getAll($page, $perPage, $filters);
            $total = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM activity_logs"
            )->cnt ?? 0;
            $totalPages = (int)ceil($total / $perPage);

            $users = $this->userModel->getActive();
            $actions = $this->db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
            $logModules = $this->db->fetchAll("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC");

            $pageTitle = 'Activity Logs';
            $content = __DIR__ . '/../views/activity/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Activity log index error', $e);
            flash('error', 'Unable to load activity logs');
            redirect('dashboard');
        }
    }
}
