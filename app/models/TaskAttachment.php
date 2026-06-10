<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskAttachment
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all attachments for a task.
     *
     * @param int $taskId
     * @return array
     */
    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT ta.*, u.first_name, u.last_name
                    FROM task_attachments ta
                    LEFT JOIN users u ON u.id = ta.user_id
                    WHERE ta.task_id = :task_id
                    ORDER BY ta.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskAttachment::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new task attachment.
     *
     * @param array $data
     * @return int The inserted attachment ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_attachments', $data);
        } catch (RuntimeException $e) {
            error_log('TaskAttachment::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete an attachment by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('task_attachments', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('TaskAttachment::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single attachment by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT ta.*, u.first_name, u.last_name
                    FROM task_attachments ta
                    LEFT JOIN users u ON u.id = ta.user_id
                    WHERE ta.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('TaskAttachment::getById - ' . $e->getMessage());
            return null;
        }
    }
}
