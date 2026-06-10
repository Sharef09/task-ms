<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class BackupHistory
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get paginated backup history.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAll(int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT bh.*, u.first_name, u.last_name
                    FROM backup_history bh
                    LEFT JOIN users u ON u.id = bh.created_by
                    ORDER BY bh.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('BackupHistory::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single backup history entry by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT bh.*, u.first_name, u.last_name
                    FROM backup_history bh
                    LEFT JOIN users u ON u.id = bh.created_by
                    WHERE bh.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('BackupHistory::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new backup history entry.
     *
     * @param array $data
     * @return int The inserted backup ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('backup_history', $data);
        } catch (RuntimeException $e) {
            error_log('BackupHistory::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete a backup history entry by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('backup_history', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('BackupHistory::delete - ' . $e->getMessage());
            return false;
        }
    }
}
