<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Report
{
    private PDO $conn;

    private array $chartColors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#5a5c69', '#858796', '#b5b5b5', '#2e59d9', '#17a673',
        '#258391', '#dda20a', '#be2617', '#3a3b45', '#6c6e7e',
    ];

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getChartData(string $type, array $filters): array
    {
        return match($type) {
            'tasks' => $this->getTaskCharts($filters),
            'users' => $this->getUserCharts($filters),
            'performance' => $this->getPerformanceCharts($filters),
            'departments' => $this->getDepartmentCharts($filters),
            'activity' => $this->getActivityCharts($filters),
            'login' => $this->getLoginCharts($filters),
            'audit' => $this->getAuditCharts($filters),
            'notifications' => $this->getNotificationCharts($filters),
            default => [],
        };
    }

    private function buildFilterWheres(array $filters, array $allowed): array
    {
        $where = [];
        $params = [];
        foreach ($allowed as $key => $column) {
            if (!empty($filters[$key])) {
                if (in_array($key, ['date_from', 'date_to'])) {
                    $op = $key === 'date_from' ? '>=' : '<=';
                    $val = $key === 'date_to' ? $filters[$key] . ' 23:59:59' : $filters[$key];
                    $where[] = "$column $op :$key";
                    $params[$key] = $val;
                } else {
                    $where[] = "$column = :$key";
                    $params[$key] = $filters[$key];
                }
            }
        }
        return [$where, $params];
    }

    private function aggregate(string $sql, array $params): array
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function buildChart(string $title, string $type, array $data, ?callable $labelFn = null, ?callable $valueFn = null): array
    {
        $labels = [];
        $values = [];
        $colors = [];
        $colorIdx = 0;
        foreach ($data as $row) {
            $labels[] = $labelFn ? $labelFn($row) : ($row->label ?? '');
            $values[] = $valueFn ? $valueFn($row) : (int)($row->value ?? 0);
            $colors[] = $this->chartColors[$colorIdx % count($this->chartColors)];
            $colorIdx++;
        }
        return [
            'title' => $title,
            'type' => $type,
            'labels' => $labels,
            'data' => $values,
            'colors' => $colors,
        ];
    }

    private function getTaskCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'status' => 't.status',
            'priority' => 't.priority',
            'department_id' => 't.department_id',
            'assigned_to' => 't.assigned_to',
            'category_id' => 't.category_id',
            'date_from' => 't.created_at',
            'date_to' => 't.created_at',
        ]);
        $where = count($w) ? 'WHERE t.deleted_at IS NULL AND ' . implode(' AND ', $w) : 'WHERE t.deleted_at IS NULL';

        $status = $this->aggregate("SELECT t.status AS label, COUNT(*) AS value FROM tasks t $where GROUP BY t.status ORDER BY value DESC", $p);
        $priority = $this->aggregate("SELECT t.priority AS label, COUNT(*) AS value FROM tasks t $where GROUP BY t.priority ORDER BY value DESC", $p);
        $dept = $this->aggregate("SELECT d.name AS label, COUNT(*) AS value FROM tasks t JOIN departments d ON d.id = t.department_id $where GROUP BY d.name ORDER BY value DESC", $p);

        return [
            $this->buildChart('Status Distribution', 'doughnut', $status),
            $this->buildChart('Priority Distribution', 'pie', $priority),
            $this->buildChart('By Department', 'doughnut', $dept),
        ];
    }

    private function getUserCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'status' => 'u.status',
            'role_id' => 'u.role_id',
            'department_id' => 'u.department_id',
            'date_from' => 'u.created_at',
            'date_to' => 'u.created_at',
        ]);
        $where = count($w) ? 'WHERE u.deleted_at IS NULL AND ' . implode(' AND ', $w) : 'WHERE u.deleted_at IS NULL';

        $role = $this->aggregate("SELECT r.name AS label, COUNT(*) AS value FROM users u JOIN roles r ON r.id = u.role_id $where GROUP BY r.name ORDER BY value DESC", $p);
        $dept = $this->aggregate("SELECT d.name AS label, COUNT(*) AS value FROM users u JOIN departments d ON d.id = u.department_id $where GROUP BY d.name ORDER BY value DESC", $p);
        $status = $this->aggregate("SELECT u.status AS label, COUNT(*) AS value FROM users u $where GROUP BY u.status ORDER BY value DESC", $p);

        return [
            $this->buildChart('By Role', 'pie', $role),
            $this->buildChart('By Department', 'doughnut', $dept),
            $this->buildChart('Status Distribution', 'pie', $status),
        ];
    }

    private function getPerformanceCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'user_id' => 'u.id',
            'department_id' => 'u.department_id',
        ]);
        $where = count($w) ? 'WHERE u.deleted_at IS NULL AND ' . implode(' AND ', $w) : 'WHERE u.deleted_at IS NULL';
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = ($filters['date_to'] ?? date('Y-m-d')) . ' 23:59:59';

        $status = $this->aggregate(
            "SELECT t.status AS label, COUNT(*) AS value FROM tasks t JOIN users u ON u.id = t.assigned_to $where AND t.created_at >= :df AND t.created_at <= :dt GROUP BY t.status ORDER BY value DESC",
            array_merge($p, ['df' => $dateFrom, 'dt' => $dateTo])
        );
        $top = $this->aggregate(
            "SELECT CONCAT(u.first_name, ' ', u.last_name) AS label, COUNT(t.id) AS value FROM users u LEFT JOIN tasks t ON t.assigned_to = u.id AND t.created_at >= :df AND t.created_at <= :dt $where GROUP BY u.id ORDER BY value DESC LIMIT 5",
            array_merge($p, ['df' => $dateFrom, 'dt' => $dateTo])
        );

        return [
            $this->buildChart('Task Status Distribution', 'doughnut', $status),
            $this->buildChart('Top 5 Users by Tasks', 'pie', $top),
        ];
    }

    private function getDepartmentCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'date_from' => 't.created_at',
            'date_to' => 't.created_at',
        ]);
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $taskDist = $this->aggregate(
            "SELECT d.name AS label, COUNT(t.id) AS value FROM departments d LEFT JOIN tasks t ON t.department_id = d.id AND t.deleted_at IS NULL $where GROUP BY d.id ORDER BY value DESC",
            $p
        );
        $userDist = $this->aggregate(
            "SELECT d.name AS label, COUNT(u.id) AS value FROM departments d LEFT JOIN users u ON u.department_id = d.id AND u.deleted_at IS NULL GROUP BY d.id ORDER BY value DESC",
            []
        );

        return [
            $this->buildChart('Task Distribution', 'doughnut', $taskDist),
            $this->buildChart('User Distribution', 'pie', $userDist),
        ];
    }

    private function getActivityCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'user_id' => 'al.user_id',
            'action' => 'al.action',
            'module' => 'al.module',
            'date_from' => 'al.created_at',
            'date_to' => 'al.created_at',
        ]);
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $action = $this->aggregate("SELECT al.action AS label, COUNT(*) AS value FROM activity_logs al $where GROUP BY al.action ORDER BY value DESC LIMIT 8", $p);
        $module = $this->aggregate("SELECT al.module AS label, COUNT(*) AS value FROM activity_logs al $where GROUP BY al.module ORDER BY value DESC LIMIT 8", $p);

        return [
            $this->buildChart('Top Actions', 'doughnut', $action),
            $this->buildChart('Top Modules', 'pie', $module),
        ];
    }

    private function getLoginCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'user_id' => 'lh.user_id',
            'date_from' => 'lh.created_at',
            'date_to' => 'lh.created_at',
        ]);
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $status = $this->aggregate("SELECT lh.status AS label, COUNT(*) AS value FROM login_logs lh $where GROUP BY lh.status ORDER BY value DESC", $p);
        $daily = $this->aggregate(
            "SELECT DATE(lh.created_at) AS label, COUNT(*) AS value FROM login_logs lh $where GROUP BY DATE(lh.created_at) ORDER BY label ASC LIMIT 14",
            $p
        );

        return [
            $this->buildChart('Login Status', 'doughnut', $status),
            $this->buildChart('Daily Logins (Last 14 Days)', 'pie', $daily),
        ];
    }

    private function getAuditCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'user_id' => 'al.user_id',
            'action' => 'al.action',
            'module' => 'al.module',
            'date_from' => 'al.created_at',
            'date_to' => 'al.created_at',
        ]);
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $action = $this->aggregate("SELECT al.action AS label, COUNT(*) AS value FROM activity_logs al $where GROUP BY al.action ORDER BY value DESC LIMIT 8", $p);
        $module = $this->aggregate("SELECT al.module AS label, COUNT(*) AS value FROM activity_logs al $where GROUP BY al.module ORDER BY value DESC LIMIT 8", $p);

        return [
            $this->buildChart('Top Actions', 'doughnut', $action),
            $this->buildChart('Top Modules', 'pie', $module),
        ];
    }

    private function getNotificationCharts(array $filters): array
    {
        [$w, $p] = $this->buildFilterWheres($filters, [
            'user_id' => 'n.user_id',
            'type' => 'n.type',
            'date_from' => 'n.created_at',
            'date_to' => 'n.created_at',
        ]);
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $type = $this->aggregate("SELECT n.type AS label, COUNT(*) AS value FROM notifications n $where GROUP BY n.type ORDER BY value DESC", $p);
        $read = $this->aggregate("SELECT CASE WHEN n.is_read = 1 THEN 'Read' ELSE 'Unread' END AS label, COUNT(*) AS value FROM notifications n $where GROUP BY n.is_read ORDER BY value DESC", $p);

        return [
            $this->buildChart('By Type', 'doughnut', $type),
            $this->buildChart('Read vs Unread', 'pie', $read),
        ];
    }

    /**
     * Get task report data with optional filters.
     *
     * @param array $filters Possible keys: status, priority, department_id, assigned_to, date_from, date_to, category_id
     * @return array
     */
    public function getTaskReport(array $filters = []): array
    {
        try {
            $where = ['t.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = 't.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['priority'])) {
                $where[] = 't.priority = :priority';
                $params['priority'] = $filters['priority'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 't.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }
            if (!empty($filters['assigned_to'])) {
                $where[] = 't.assigned_to = :assigned_to';
                $params['assigned_to'] = $filters['assigned_to'];
            }
            if (!empty($filters['category_id'])) {
                $where[] = 't.category_id = :category_id';
                $params['category_id'] = $filters['category_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 't.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 't.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    {$whereClause}
                    ORDER BY t.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getTaskReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user report data with optional filters.
     *
     * @param array $filters Possible keys: status, role_id, department_id, date_from, date_to
     * @return array
     */
    public function getUserReport(array $filters = []): array
    {
        try {
            $where = ['u.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = 'u.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['role_id'])) {
                $where[] = 'u.role_id = :role_id';
                $params['role_id'] = $filters['role_id'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 'u.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'u.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'u.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name,
                           (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id) AS tasks_assigned,
                           (SELECT COUNT(*) FROM tasks WHERE created_by = u.id) AS tasks_created
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    {$whereClause}
                    ORDER BY u.first_name, u.last_name";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getUserReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance report data with optional filters.
     *
     * @param array $filters Possible keys: user_id, department_id, date_from, date_to
     * @return array
     */
    public function getPerformanceReport(array $filters = []): array
    {
        try {
            $where = ['u.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'u.id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 'u.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = ($filters['date_to'] ?? date('Y-m-d')) . ' 23:59:59';

            $sql = "SELECT u.id, u.first_name, u.last_name,
                           COUNT(t.id) AS total_tasks,
                           SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                           SUM(CASE WHEN t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) AS overdue_tasks,
                           ROUND(AVG(CASE WHEN t.status = 'completed' THEN TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at) ELSE NULL END), 1) AS avg_completion_hours
                    FROM users u
                    LEFT JOIN tasks t ON t.assigned_to = u.id
                        AND t.created_at >= :date_from AND t.created_at <= :date_to
                    {$whereClause}
                    GROUP BY u.id
                    ORDER BY completed_tasks DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':date_from', $dateFrom);
            $stmt->bindValue(':date_to', $dateTo);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getPerformanceReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department report data with optional filters.
     *
     * @param array $filters Possible keys: date_from, date_to
     * @return array
     */
    public function getDepartmentReport(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['date_from'])) {
                $where[] = 't.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 't.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT d.id, d.name AS department_name,
                           COUNT(t.id) AS total_tasks,
                           SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
                           SUM(CASE WHEN t.status = 'Assigned' THEN 1 ELSE 0 END) AS pending_tasks,
                           SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
                           COUNT(DISTINCT t.assigned_to) AS active_users,
                           COUNT(DISTINCT u.id) AS total_users
                    FROM departments d
                    LEFT JOIN users u ON u.department_id = d.id AND u.deleted_at IS NULL
                    LEFT JOIN tasks t ON t.department_id = d.id AND t.deleted_at IS NULL
                    {$whereClause}
                    GROUP BY d.id
                    ORDER BY d.name ASC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getDepartmentReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity report data with optional filters.
     *
     * @param array $filters Possible keys: user_id, action, module, date_from, date_to
     * @return array
     */
    public function getActivityReport(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'al.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['action'])) {
                $where[] = 'al.action = :action';
                $params['action'] = $filters['action'];
            }
            if (!empty($filters['module'])) {
                $where[] = 'al.module = :module';
                $params['module'] = $filters['module'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'al.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'al.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT al.*, u.first_name, u.last_name
                    FROM activity_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getActivityReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get login report data with optional filters.
     *
     * @param array $filters Possible keys: user_id, date_from, date_to
     * @return array
     */
    public function getLoginReport(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'lh.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'lh.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'lh.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT lh.*, u.first_name, u.last_name, u.email
                    FROM login_logs lh
                    LEFT JOIN users u ON u.id = lh.user_id
                    {$whereClause}
                    ORDER BY lh.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getLoginReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit report data with optional filters.
     *
     * @param array $filters Possible keys: user_id, action, module, date_from, date_to
     * @return array
     */
    public function getAuditReport(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'al.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['action'])) {
                $where[] = 'al.action = :action';
                $params['action'] = $filters['action'];
            }
            if (!empty($filters['module'])) {
                $where[] = 'al.module = :module';
                $params['module'] = $filters['module'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'al.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'al.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getAuditReport - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get notification report data with optional filters.
     *
     * @param array $filters Possible keys: user_id, type, date_from, date_to
     * @return array
     */
    public function getNotificationReport(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'n.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['type'])) {
                $where[] = 'n.type = :type';
                $params['type'] = $filters['type'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'n.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'n.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT n.*, u.first_name, u.last_name
                    FROM notifications n
                    LEFT JOIN users u ON u.id = n.user_id
                    {$whereClause}
                    ORDER BY n.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Report::getNotificationReport - ' . $e->getMessage());
            return [];
        }
    }
}
