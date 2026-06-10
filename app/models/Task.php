<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Task
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get paginated list of tasks with optional filters.
     *
     * @param int   $page
     * @param int   $perPage
     * @param array $filters Possible keys: search, status, priority, department_id, assigned_to, category_id, date_from, date_to
     * @return array
     */
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(t.title LIKE :search OR t.description LIKE :search2)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }
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
            if (!empty($filters['task_type'])) {
                $where[] = 't.task_type = :task_type';
                $params['task_type'] = $filters['task_type'];
            }
            if (!empty($filters['user_id'])) {
                $where[] = '(t.assigned_to = :user_id OR t.created_by = :user_id2)';
                $params['user_id'] = (int)$filters['user_id'];
                $params['user_id2'] = (int)$filters['user_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 't.due_date >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 't.due_date <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT t.*, 
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           assigner.first_name AS assigned_by_first_name,
                           assigner.last_name AS assigned_by_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users assigner ON assigner.id = t.assigned_by
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    {$whereClause}
                    ORDER BY t.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single task by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           assigner.first_name AS assigned_by_first_name,
                           assigner.last_name AS assigned_by_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users assigner ON assigner.id = t.assigned_by
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    WHERE t.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Task::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @return int The inserted task ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('tasks', $data);
        } catch (RuntimeException $e) {
            error_log('Task::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing task.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('tasks', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Task::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a task by ID (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->softDelete('tasks', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Task::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tasks assigned to a specific user with pagination.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getByUser(int $userId, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
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
                    WHERE t.assigned_to = :user_id AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tasks by department ID.
     *
     * @param int $deptId
     * @return array
     */
    public function getByDepartment(int $deptId): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE t.department_id = :dept_id AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':dept_id', $deptId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getByDepartment - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tasks by status.
     *
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE t.status = :status AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getByStatus - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overdue tasks.
     *
     * @return array
     */
    public function getOverdue(): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users created ON created.id = t.created_by
                    WHERE t.due_date < NOW()
                       AND t.status NOT IN ('Completed', 'Cancelled')
                      AND t.deleted_at IS NULL
                    ORDER BY t.due_date ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getOverdue - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update task status.
     *
     * @param int    $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getInstance();
            $data = ['status' => $status];
            if ($status === 'Completed') {
                $data['completed_at'] = date('Y-m-d H:i:s');
            }
            $db->update('tasks', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Task::updateStatus - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign a task to a user.
     *
     * @param int $id
     * @param int $assignedTo
     * @param int $assignedBy
     * @return bool
     */
    public function assignTask(int $id, int $assignedTo, int $assignedBy): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('tasks', [
                'assigned_to'   => $assignedTo,
                'assigned_by'   => $assignedBy,
                'assigned_at'   => date('Y-m-d H:i:s'),
                'status'        => 'Assigned',
            ], 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Task::assignTask - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reassign a task from one user to another.
     *
     * @param int $id
     * @param int $newAssigneeId
     * @param int $oldAssigneeId
     * @param int $userId
     * @return bool
     */
    public function reassignTask(int $id, int $newAssigneeId, int $oldAssigneeId, int $userId): bool
    {
        try {
            $db = Database::getInstance();
            $this->conn->beginTransaction();

            $db->update('tasks', [
                'assigned_to'   => $newAssigneeId,
                'assigned_by'   => $userId,
                'assigned_at'   => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);

            $this->conn->commit();
            return true;
        } catch (RuntimeException $e) {
            $this->conn->rollBack();
            error_log('Task::reassignTask - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get task statistics for a specific user (tasks they created or are assigned to).
     *
     * @param int $userId
     * @return object|null
     */
    public function getUserStats(int $userId): ?object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS total_tasks,
                        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_tasks,
                        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS assigned,
                        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                        SUM(CASE WHEN status = 'On Hold' THEN 1 ELSE 0 END) AS on_hold,
                        SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) AS overdue,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_tasks,
                        SUM(CASE WHEN status = 'Completed' AND DATE(completed_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today
                    FROM tasks
                    WHERE deleted_at IS NULL
                      AND (assigned_to = :user_id OR created_by = :user_id2)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Task::getUserStats - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get task statistics.
     *
     * @return object|null
     */
    public function getStats(): ?object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS total_tasks,
                        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_tasks,
                        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS assigned,
                        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                        SUM(CASE WHEN status = 'On Hold' THEN 1 ELSE 0 END) AS on_hold,
                        SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) AS overdue,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_tasks,
                        SUM(CASE WHEN status = 'Completed' AND DATE(completed_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today
                    FROM tasks
                    WHERE deleted_at IS NULL";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Task::getStats - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get tasks created today.
     *
     * @return array
     */
    public function getTodayTasks(): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE DATE(t.created_at) = CURDATE() AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getTodayTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tasks created this week.
     *
     * @return array
     */
    public function getWeekTasks(): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE YEARWEEK(t.created_at) = YEARWEEK(CURDATE()) AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getWeekTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tasks created this month.
     *
     * @return array
     */
    public function getMonthTasks(): array
    {
        try {
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE MONTH(t.created_at) = MONTH(CURDATE())
                      AND YEAR(t.created_at) = YEAR(CURDATE())
                      AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getMonthTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get task counts grouped by status.
     *
     * @return array
     */
    public function getTaskCountsByStatus(): array
    {
        try {
            $sql = "SELECT status, COUNT(*) AS total
                    FROM tasks
                    WHERE deleted_at IS NULL
                    GROUP BY status
                    ORDER BY total DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getTaskCountsByStatus - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get task counts grouped by priority.
     *
     * @return array
     */
    public function getTaskCountsByPriority(): array
    {
        try {
            $sql = "SELECT priority, COUNT(*) AS total
                    FROM tasks
                    WHERE deleted_at IS NULL
                    GROUP BY priority
                    ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low')";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getTaskCountsByPriority - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search tasks by keyword.
     *
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        try {
            $search = '%' . $query . '%';
            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    WHERE (t.title LIKE :q1 OR t.description LIKE :q2)
                      AND t.deleted_at IS NULL
                    ORDER BY t.created_at DESC
                    LIMIT 50";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':q1', $search);
            $stmt->bindValue(':q2', $search);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::search - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tasks assigned to or shared with a user with pagination & filters.
     *
     * @param int   $userId
     * @param int   $page
     * @param int   $perPage
     * @param array $filters Possible keys: search, status, priority
     * @return array
     */
    public function getMyTasks(int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $where = ['(t.assigned_to = :user_id OR t.id IN (SELECT task_id FROM task_assignments WHERE user_id = :user_id2))', 't.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(t.title LIKE :search OR t.description LIKE :search2)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status'])) {
                $where[] = 't.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['priority'])) {
                $where[] = 't.priority = :priority';
                $params['priority'] = $filters['priority'];
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           assigner.first_name AS assigned_by_first_name,
                           assigner.last_name AS assigned_by_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users assigner ON assigner.id = t.assigned_by
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    {$whereClause}
                    ORDER BY t.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getMyTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of my tasks.
     *
     * @param int $userId
     * @return int
     */
    public function getMyTasksCount(int $userId): int
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM tasks
                    WHERE (assigned_to = ? OR id IN (SELECT task_id FROM task_assignments WHERE user_id = ?))
                      AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (RuntimeException $e) {
            error_log('Task::getMyTasksCount - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get tasks created by a user with pagination & filters.
     *
     * @param int   $userId
     * @param int   $page
     * @param int   $perPage
     * @param array $filters Possible keys: search, status, priority
     * @return array
     */
    public function getSentTasks(int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $where = ['t.created_by = :user_id', 't.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(t.title LIKE :search OR t.description LIKE :search2)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status'])) {
                $where[] = 't.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['priority'])) {
                $where[] = 't.priority = :priority';
                $params['priority'] = $filters['priority'];
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           assigner.first_name AS assigned_by_first_name,
                           assigner.last_name AS assigned_by_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users assigner ON assigner.id = t.assigned_by
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    {$whereClause}
                    ORDER BY t.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getSentTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of sent tasks.
     *
     * @param int $userId
     * @return int
     */
    public function getSentTasksCount(int $userId): int
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM tasks
                    WHERE created_by = ? AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (RuntimeException $e) {
            error_log('Task::getSentTasksCount - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get rejected/escalated/disputed tasks for a user with pagination & filters.
     *
     * @param int   $userId
     * @param int   $page
     * @param int   $perPage
     * @param array $filters Possible keys: search, status, priority
     * @return array
     */
    public function getAbusedTasks(int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $where = ['(t.status = \'Rejected\' OR t.escalated = 1 OR t.disputed = 1)', 't.deleted_at IS NULL',
                      '(t.assigned_to = :user_id OR t.id IN (SELECT task_id FROM task_assignments WHERE user_id = :user_id2))'];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(t.title LIKE :search OR t.description LIKE :search2)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status'])) {
                $where[] = 't.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['priority'])) {
                $where[] = 't.priority = :priority';
                $params['priority'] = $filters['priority'];
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT t.*,
                           assigned.first_name AS assigned_first_name,
                           assigned.last_name AS assigned_last_name,
                           assigner.first_name AS assigned_by_first_name,
                           assigner.last_name AS assigned_by_last_name,
                           created.first_name AS created_first_name,
                           created.last_name AS created_last_name,
                           d.name AS department_name,
                           tc.name AS category_name
                    FROM tasks t
                    LEFT JOIN users assigned ON assigned.id = t.assigned_to
                    LEFT JOIN users assigner ON assigner.id = t.assigned_by
                    LEFT JOIN users created ON created.id = t.created_by
                    LEFT JOIN departments d ON d.id = t.department_id
                    LEFT JOIN task_categories tc ON tc.id = t.category_id
                    {$whereClause}
                    ORDER BY t.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getAbusedTasks - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of abused tasks.
     *
     * @return int
     */
    public function getAbusedTasksCount(): int
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM tasks
                    WHERE (status = 'Rejected' OR escalated = 1 OR disputed = 1)
                      AND deleted_at IS NULL";
            $stmt = $this->conn->query($sql);
            return (int) $stmt->fetchColumn();
        } catch (RuntimeException $e) {
            error_log('Task::getAbusedTasksCount - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get workload statistics grouped by user.
     *
     * @return array
     */
    public function getWorkloadByUser(): array
    {
        try {
            $sql = "SELECT u.id, u.first_name, u.last_name,
                           COUNT(t.id) AS total_tasks,
                           SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
                           SUM(CASE WHEN t.status = 'Overdue' THEN 1 ELSE 0 END) AS overdue,
                           SUM(CASE WHEN t.status IN ('Completed','Cancelled') THEN 1 ELSE 0 END) AS completed
                    FROM users u
                    LEFT JOIN tasks t ON (t.assigned_to = u.id OR u.id IN (SELECT user_id FROM task_assignments WHERE task_id = t.id))
                      AND t.deleted_at IS NULL
                    WHERE u.deleted_at IS NULL AND u.status = 'Active'
                    GROUP BY u.id
                    ORDER BY total_tasks DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getWorkloadByUser - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department performance statistics.
     *
     * @return array
     */
    public function getDepartmentPerformance(): array
    {
        try {
            $sql = "SELECT d.id, d.name,
                           COUNT(t.id) AS total_tasks,
                           SUM(CASE WHEN t.status IN ('Completed','Cancelled') THEN 1 ELSE 0 END) AS completed,
                           ROUND(AVG(t.progress_percentage), 1) AS avg_progress
                    FROM departments d
                    LEFT JOIN tasks t ON t.department_id = d.id AND t.deleted_at IS NULL
                    GROUP BY d.id
                    ORDER BY total_tasks DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Task::getDepartmentPerformance - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get completion statistics, optionally filtered by user.
     *
     * @param int|null $userId
     * @return object|null
     */
    public function getCompletionStats(int $userId = null): ?object
    {
        try {
            $sql = "SELECT COUNT(*) as total,
                           SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue,
                           SUM(CASE WHEN status IN ('Draft','Pending') THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress
                    FROM tasks
                    WHERE deleted_at IS NULL";

            if ($userId !== null) {
                $sql .= " AND (assigned_to = ? OR id IN (SELECT task_id FROM task_assignments WHERE user_id = ?))";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->bindValue(2, $userId, PDO::PARAM_INT);
            } else {
                $stmt = $this->conn->prepare($sql);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Task::getCompletionStats - ' . $e->getMessage());
            return null;
        }
    }
}
