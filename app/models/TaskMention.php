<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskMention
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByUser(int $userId, int $limit = 10): array
    {
        try {
            $sql = "SELECT tm.*, t.title AS task_title, t.status AS task_status,
                           u.first_name, u.last_name
                    FROM task_mentions tm
                    LEFT JOIN tasks t ON t.id = tm.task_id
                    LEFT JOIN users u ON u.id = tm.mentioned_by
                    WHERE tm.user_id = :user_id
                    ORDER BY tm.created_at DESC
                    LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskMention::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_mentions', $data);
        } catch (RuntimeException $e) {
            error_log('TaskMention::create - ' . $e->getMessage());
            return 0;
        }
    }
}
