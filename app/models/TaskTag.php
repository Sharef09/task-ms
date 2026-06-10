<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskTag
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT tt.*, t.name, t.color
                    FROM task_tags tt
                    LEFT JOIN tags t ON t.id = tt.tag_id
                    WHERE tt.task_id = :task_id
                    ORDER BY t.name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskTag::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    public function setTags(int $taskId, array $tags): void
    {
        try {
            $this->conn->beginTransaction();

            $deleteSql = "DELETE FROM task_tags WHERE task_id = :task_id";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $deleteStmt->execute();

            if (!empty($tags)) {
                $insertSql = "INSERT INTO task_tags (task_id, tag_id) VALUES (:task_id, :tag_id)";
                $insertStmt = $this->conn->prepare($insertSql);
                foreach ($tags as $tagId) {
                    $insertStmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
                    $insertStmt->bindValue(':tag_id', (int) $tagId, PDO::PARAM_INT);
                    $insertStmt->execute();
                }
            }

            $this->conn->commit();
        } catch (RuntimeException $e) {
            $this->conn->rollBack();
            error_log('TaskTag::setTags - ' . $e->getMessage());
        }
    }
}
