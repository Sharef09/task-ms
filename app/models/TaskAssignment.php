<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskAssignment
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT ta.*, u.first_name, u.last_name, u.avatar
                    FROM task_assignments ta
                    LEFT JOIN users u ON u.id = ta.user_id
                    WHERE ta.task_id = :task_id
                    ORDER BY ta.assigned_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskAssignment::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUser(int $userId, int $page = 1, int $perPage = 15): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT ta.*, t.title, t.status, t.priority, t.due_date
                    FROM task_assignments ta
                    LEFT JOIN tasks t ON t.id = ta.task_id
                    WHERE ta.user_id = :user_id
                    ORDER BY ta.assigned_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskAssignment::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserCount(int $userId): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM task_assignments WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        } catch (RuntimeException $e) {
            error_log('TaskAssignment::getByUserCount - ' . $e->getMessage());
            return 0;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_assignments', $data);
        } catch (RuntimeException $e) {
            error_log('TaskAssignment::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteByTask(int $taskId): void
    {
        try {
            $sql = "DELETE FROM task_assignments WHERE task_id = :task_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('TaskAssignment::deleteByTask - ' . $e->getMessage());
        }
    }
}
