<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskCategory
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all task categories.
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM task_categories ORDER BY name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('TaskCategory::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single task category by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT * FROM task_categories WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('TaskCategory::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new task category.
     *
     * @param array $data
     * @return int The inserted category ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_categories', $data);
        } catch (RuntimeException $e) {
            error_log('TaskCategory::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing task category.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('task_categories', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('TaskCategory::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a task category by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('task_categories', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('TaskCategory::delete - ' . $e->getMessage());
            return false;
        }
    }
}
