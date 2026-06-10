<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Department
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all departments.
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM departments ORDER BY name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Department::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single department by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT * FROM departments WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Department::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new department.
     *
     * @param array $data
     * @return int The inserted department ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('departments', $data);
        } catch (RuntimeException $e) {
            error_log('Department::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing department.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('departments', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Department::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a department by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('departments', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Department::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of users in a department.
     *
     * @param int $deptId
     * @return int
     */
    public function getUsersCount(int $deptId): int
    {
        try {
            $sql = "SELECT COUNT(*) AS total FROM users WHERE department_id = :dept_id AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':dept_id', $deptId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        } catch (RuntimeException $e) {
            error_log('Department::getUsersCount - ' . $e->getMessage());
            return 0;
        }
    }
}
