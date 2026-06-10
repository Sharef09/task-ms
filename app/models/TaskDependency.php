<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskDependency
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT td.*, t.title, t.status
                    FROM task_dependencies td
                    LEFT JOIN tasks t ON t.id = td.depends_on_task_id
                    WHERE td.task_id = :task_id
                    ORDER BY td.created_at ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskDependency::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_dependencies', $data);
        } catch (RuntimeException $e) {
            error_log('TaskDependency::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function delete(int $id): void
    {
        try {
            $sql = "DELETE FROM task_dependencies WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('TaskDependency::delete - ' . $e->getMessage());
        }
    }
}
