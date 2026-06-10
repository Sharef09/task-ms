<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskWatcher
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT tw.*, u.first_name, u.last_name, u.avatar
                    FROM task_watchers tw
                    LEFT JOIN users u ON u.id = tw.user_id
                    WHERE tw.task_id = :task_id
                    ORDER BY tw.created_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskWatcher::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    public function addWatcher(int $taskId, int $userId): int
    {
        try {
            $sql = "INSERT IGNORE INTO task_watchers (task_id, user_id, created_at)
                    VALUES (:task_id, :user_id, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $this->conn->lastInsertId();
        } catch (RuntimeException $e) {
            error_log('TaskWatcher::addWatcher - ' . $e->getMessage());
            return 0;
        }
    }

    public function removeWatcher(int $taskId, int $userId): void
    {
        try {
            $sql = "DELETE FROM task_watchers WHERE task_id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('TaskWatcher::removeWatcher - ' . $e->getMessage());
        }
    }

    public function isWatching(int $taskId, int $userId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as cnt FROM task_watchers WHERE task_id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->cnt > 0;
        } catch (RuntimeException $e) {
            error_log('TaskWatcher::isWatching - ' . $e->getMessage());
            return false;
        }
    }
}
