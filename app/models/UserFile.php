<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class UserFile
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByUser(int $userId, string $folder = '/'): array
    {
        try {
            $sql = "SELECT uf.*, u.first_name, u.last_name
                    FROM user_files uf
                    LEFT JOIN users u ON u.id = uf.user_id
                    WHERE uf.user_id = :user_id AND uf.folder = :folder
                    ORDER BY uf.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':folder', $folder, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('UserFile::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getByTask(int $taskId): array
    {
        try {
            $sql = "SELECT uf.*, u.first_name, u.last_name
                    FROM user_files uf
                    LEFT JOIN users u ON u.id = uf.user_id
                    WHERE uf.task_id = :task_id
                    ORDER BY uf.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('UserFile::getByTask - ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT uf.*, u.first_name, u.last_name
                    FROM user_files uf
                    LEFT JOIN users u ON u.id = uf.user_id
                    WHERE uf.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('UserFile::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('user_files', $data);
        } catch (RuntimeException $e) {
            error_log('UserFile::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function delete(int $id): void
    {
        try {
            $sql = "DELETE FROM user_files WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('UserFile::delete - ' . $e->getMessage());
        }
    }
}
