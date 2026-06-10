<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Task;
use App\Models\Notification;

class DashboardController
{
    private Database $db;
    private Session $session;
    private Task $taskModel;
    private Notification $notificationModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->taskModel = new Task();
        $this->notificationModel = new Notification();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $user = $this->session->get('user');
            $isStaff = isStaff();
            $this->autoSetOverdue();

            if ($isStaff) {
                $stats = $this->taskModel->getUserStats($user->id);
            } else {
                $stats = $this->taskModel->getStats();
            }

            $totalUsers = null;
            $activeUsers = null;
            $recentLogins = [];
            $recentActivity = [];
            $topPerformers = [];

            if (!$isStaff) {
                $totalUsers = $this->db->fetch("SELECT COUNT(*) as cnt FROM users WHERE deleted_at IS NULL");
                $activeUsers = $this->db->fetch("SELECT COUNT(*) as cnt FROM users WHERE status = 'Active' AND deleted_at IS NULL");

                $topPerformers = $this->db->fetchAll(
                    "SELECT u.id, u.first_name, u.last_name, u.avatar,
                            COUNT(t.id) as completed_tasks
                     FROM users u
                     JOIN tasks t ON t.assigned_to = u.id AND t.status = 'Completed'
                     WHERE t.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY u.id
                     ORDER BY completed_tasks DESC
                     LIMIT 10"
                );

                $recentLogins = $this->db->fetchAll(
                    "SELECT ll.*, u.first_name, u.last_name
                     FROM login_logs ll
                     LEFT JOIN users u ON u.id = ll.user_id
                     ORDER BY ll.created_at DESC LIMIT 5"
                );

                $recentActivity = $this->db->fetchAll(
                    "SELECT al.*, u.first_name, u.last_name, u.avatar
                     FROM activity_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     ORDER BY al.created_at DESC LIMIT 10"
                );
            }

            $s = $stats ?: new \stdClass();
            $s->total_users     = (int)(($totalUsers->cnt ?? 0));
            $s->active_users    = (int)(($activeUsers->cnt ?? 0));
            $s->inactive_users  = max(0, $s->total_users - $s->active_users);
            $s->total_tasks     = (int)($s->total_tasks ?? 0);
            $s->open_tasks      = (int)($s->open_tasks ?? 0);
            $s->assigned        = (int)($s->assigned ?? 0);
            $s->in_progress     = (int)($s->in_progress ?? 0);
            $s->completed       = (int)($s->completed ?? 0);
            $s->overdue         = (int)($s->overdue ?? 0);
            $s->today_tasks     = (int)($s->today_tasks ?? 0);
            $s->completed_today = (int)($s->completed_today ?? 0);
            $stats = $s;

            $userId = (int)$user->id;
            $userTaskFilter = $isStaff
                ? "AND (assigned_to = {$userId} OR created_by = {$userId})"
                : '';

            $thisWeek = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM tasks WHERE deleted_at IS NULL
                 AND YEARWEEK(created_at) = YEARWEEK(CURDATE()) $userTaskFilter"
            );
            $stats->this_week_tasks = (int)($thisWeek->cnt ?? 0);

            $thisMonth = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM tasks WHERE deleted_at IS NULL
                 AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) $userTaskFilter"
            );
            $stats->this_month_tasks = (int)($thisMonth->cnt ?? 0);

            $taskStatusRaw = $this->db->fetchAll(
                "SELECT status, COUNT(*) as total FROM tasks WHERE deleted_at IS NULL $userTaskFilter GROUP BY status"
            );
            $taskStatusData = [];
            foreach ($taskStatusRaw as $row) {
                $taskStatusData[] = ['label' => $row->status, 'value' => (int)$row->total];
            }

            $taskPriorityRaw = $this->db->fetchAll(
                "SELECT priority, COUNT(*) as total FROM tasks WHERE deleted_at IS NULL $userTaskFilter GROUP BY priority"
            );
            $taskPriorityData = [];
            foreach ($taskPriorityRaw as $row) {
                $taskPriorityData[] = ['label' => $row->priority, 'value' => (int)$row->total];
            }

            $monthlyRaw = $this->db->fetchAll(
                "SELECT DATE_FORMAT(completed_at, '%Y-%m') as month, COUNT(*) as total
                 FROM tasks WHERE completed_at IS NOT NULL AND completed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 $userTaskFilter
                 GROUP BY DATE_FORMAT(completed_at, '%Y-%m') ORDER BY month ASC"
            );
            $monthlyTrendData = [];
            foreach ($monthlyRaw as $row) {
                $monthlyTrendData[] = ['label' => $row->month, 'value' => (int)$row->total];
            }

            $departmentRaw = $this->db->fetchAll(
                "SELECT d.name, COUNT(t.id) as completed
                 FROM departments d
                 LEFT JOIN tasks t ON t.department_id = d.id AND t.status = 'Completed' AND t.deleted_at IS NULL
                 WHERE d.deleted_at IS NULL
                 GROUP BY d.id
                 ORDER BY completed DESC LIMIT 10"
            );
            $departmentData = [];
            foreach ($departmentRaw as $row) {
                $departmentData[] = ['label' => $row->name, 'value' => (int)$row->completed];
            }

            $recentTasks = $this->db->fetchAll(
                "SELECT t.*, assigned.first_name as assigned_first_name, assigned.last_name as assigned_last_name
                 FROM tasks t
                 LEFT JOIN users assigned ON assigned.id = t.assigned_to
                 WHERE t.deleted_at IS NULL $userTaskFilter
                 ORDER BY t.created_at DESC LIMIT 10"
            );

            $notifications = $this->notificationModel->getRecent($user->id, 10);
            $unreadCount = $this->notificationModel->getUnreadCount($user->id);

            $upcomingDeadlines = $this->db->fetchAll(
                "SELECT t.*, assigned.first_name as assigned_first_name, assigned.last_name as assigned_last_name
                 FROM tasks t
                 LEFT JOIN users assigned ON assigned.id = t.assigned_to
                 WHERE t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                   AND t.status NOT IN ('Completed', 'Cancelled')
                   AND t.deleted_at IS NULL $userTaskFilter
                 ORDER BY t.due_date ASC LIMIT 10"
            );

            $overdueTasks = $this->db->fetchAll(
                "SELECT t.*, assigned.first_name as assigned_first_name, assigned.last_name as assigned_last_name
                 FROM tasks t
                 LEFT JOIN users assigned ON assigned.id = t.assigned_to
                 WHERE t.due_date < NOW() AND t.status NOT IN ('Completed', 'Cancelled') AND t.deleted_at IS NULL $userTaskFilter
                 ORDER BY t.due_date ASC LIMIT 10"
            );

            $pageTitle = 'Dashboard';
            $content = __DIR__ . '/../views/dashboard/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Dashboard error', $e);
            flash('error', 'Unable to load dashboard');
            redirect('login');
        }
    }

    private function autoSetOverdue(): void
    {
        try {
            $this->db->query(
                "UPDATE tasks SET status = 'Overdue'
                 WHERE due_date < NOW()
                   AND status NOT IN ('Completed', 'Cancelled', 'Overdue')
                   AND deleted_at IS NULL"
            );
        } catch (\Throwable $e) {
            logError('Auto-set overdue failed', $e);
        }
    }
}
