<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class User
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get paginated list of users with optional filters.
     *
     * @param int    $page
     * @param int    $perPage
     * @param array  $filters Possible keys: search, status, role_id, department_id, date_from, date_to
     * @return array
     */
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search2 OR u.email LIKE :search3)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
                $params['search3'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status'])) {
                $where[] = 'u.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['role_id'])) {
                $where[] = 'u.role_id = :role_id';
                $params['role_id'] = $filters['role_id'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 'u.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'u.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'u.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    {$whereClause}
                    ORDER BY u.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single user by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT u.*, r.name AS role_name, r.slug AS role_slug, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('User::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return int The inserted user ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('users', $data);
        } catch (RuntimeException $e) {
            error_log('User::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing user.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('users', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('User::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user by ID (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->softDelete('users', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('User::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users by role ID.
     *
     * @param int $roleId
     * @return array
     */
    public function getByRole(int $roleId): array
    {
        try {
            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.role_id = :role_id
                    ORDER BY u.first_name, u.last_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::getByRole - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by department ID.
     *
     * @param int $deptId
     * @return array
     */
    public function getByDepartment(int $deptId): array
    {
        try {
            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.department_id = :dept_id
                    ORDER BY u.first_name, u.last_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':dept_id', $deptId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::getByDepartment - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all active users.
     *
     * @return array
     */
    public function getActive(): array
    {
        try {
            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.status = 'active'
                    ORDER BY u.first_name, u.last_name";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::getActive - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search users by keyword.
     *
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        try {
            $search = '%' . $query . '%';
            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.email LIKE :q3
                    ORDER BY u.first_name, u.last_name
                    LIMIT 50";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':q1', $search);
            $stmt->bindValue(':q2', $search);
            $stmt->bindValue(':q3', $search);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::search - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by status.
     *
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        try {
            $sql = "SELECT u.*, r.name AS role_name, d.name AS department_name
                    FROM users u
                    LEFT JOIN roles r ON r.id = u.role_id
                    LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.status = :status
                    ORDER BY u.first_name, u.last_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('User::getByStatus - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update user status.
     *
     * @param int    $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('users', ['status' => $status], 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('User::updateStatus - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset user password.
     *
     * @param int    $id
     * @param string $password Hashed password
     * @return bool
     */
    public function resetPassword(int $id, string $password): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('users', ['password' => $password], 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('User::resetPassword - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count of users with optional filters.
     *
     * @param array $filters Possible keys: search, status, role_id, department_id
     * @return int
     */
    public function getTotalCount(array $filters = []): int
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(first_name LIKE :search OR last_name LIKE :search2 OR email LIKE :search3)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
                $params['search3'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status'])) {
                $where[] = 'status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['role_id'])) {
                $where[] = 'role_id = :role_id';
                $params['role_id'] = $filters['role_id'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 'department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT COUNT(*) AS total FROM users {$whereClause}";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        } catch (RuntimeException $e) {
            error_log('User::getTotalCount - ' . $e->getMessage());
            return 0;
        }
    }
}
