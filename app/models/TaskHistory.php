<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskHistory
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get history entries for a task.
     *
     * @param int $taskId
     * @return array
     */
    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT th.*, u.first_name, u.last_name
                    FROM task_history th
                    LEFT JOIN users u ON u.id = th.user_id
                    WHERE th.task_id = :task_id
                    ORDER BY th.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskHistory::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new task history entry.
     *
     * @param array $data
     * @return int The inserted history ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_history', $data);
        } catch (RuntimeException $e) {
            error_log('TaskHistory::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent history entries for a specific user.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentByUser(int $userId, int $limit): array
    {
        try {
            $sql = "SELECT th.*, t.title AS task_title
                    FROM task_history th
                    LEFT JOIN tasks t ON t.id = th.task_id
                    WHERE th.user_id = :user_id
                    ORDER BY th.created_at DESC
                    LIMIT :lim";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskHistory::getRecentByUser - ' . $e->getMessage());
            return [];
        }
    }
}
