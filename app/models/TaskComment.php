<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskComment
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all comments for a task.
     *
     * @param int $taskId
     * @return array
     */
    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT tc.*, u.first_name, u.last_name, u.avatar
                    FROM task_comments tc
                    LEFT JOIN users u ON u.id = tc.user_id
                    WHERE tc.task_id = :task_id
                    ORDER BY tc.created_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskComment::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new task comment.
     *
     * @param array $data
     * @return int The inserted comment ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_comments', $data);
        } catch (RuntimeException $e) {
            error_log('TaskComment::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete a comment by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('task_comments', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('TaskComment::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get internal comments for a task (non-public).
     *
     * @param int $taskId
     * @return array
     */
    public function getInternalByTask(int $taskId): array
    {
        try {
            $sql = "SELECT tc.*, u.first_name, u.last_name
                    FROM task_comments tc
                    LEFT JOIN users u ON u.id = tc.user_id
                    WHERE tc.task_id = :task_id AND tc.is_internal = 1
                    ORDER BY tc.created_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskComment::getInternalByTask - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get staff (non-internal) comments for a task.
     *
     * @param int $taskId
     * @return array
     */
    public function getStaffComments(int $taskId): array
    {
        try {
            $sql = "SELECT tc.*, u.first_name, u.last_name
                    FROM task_comments tc
                    LEFT JOIN users u ON u.id = tc.user_id
                    WHERE tc.task_id = :task_id AND (tc.is_internal = 0 OR tc.is_internal IS NULL)
                    ORDER BY tc.created_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskComment::getStaffComments - ' . $e->getMessage());
            return [];
        }
    }
}
