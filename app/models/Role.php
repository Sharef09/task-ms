<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Role
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all roles.
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM roles ORDER BY name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Role::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single role by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT * FROM roles WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Role::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new role.
     *
     * @param array $data
     * @return int The inserted role ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('roles', $data);
        } catch (RuntimeException $e) {
            error_log('Role::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing role.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('roles', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Role::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a role by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('roles', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Role::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get permissions assigned to a role.
     *
     * @param int $roleId
     * @return array
     */
    public function getPermissions(int $roleId): array
    {
        try {
            $sql = "SELECT p.*
                    FROM permissions p
                    INNER JOIN role_permissions rp ON rp.permission_id = p.id
                    WHERE rp.role_id = :role_id
                    ORDER BY p.module, p.name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Role::getPermissions - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set (sync) permissions for a role.
     *
     * @param int   $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function setPermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $db = Database::getInstance();
            $this->conn->beginTransaction();

            $db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $roleId]);

            foreach ($permissionIds as $permId) {
                $db->insert('role_permissions', [
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (RuntimeException $e) {
            $this->conn->rollBack();
            error_log('Role::setPermissions - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clone a role with a new name, including its permissions.
     *
     * @param int    $id
     * @param string $newName
     * @return int The new role ID
     */
    public function cloneRole(int $id, string $newName): int
    {
        try {
            $db = Database::getInstance();
            $original = $this->getById($id);
            if (!$original) {
                return 0;
            }

            $this->conn->beginTransaction();

            $newRoleId = $db->insert('roles', [
                'name'        => $newName,
                'description' => $original->description ?? '',
                'is_system'   => 0,
            ]);

            $perms = $this->getPermissions($id);
            foreach ($perms as $perm) {
                $db->insert('role_permissions', [
                    'role_id'       => $newRoleId,
                    'permission_id' => $perm->id,
                ]);
            }

            $this->conn->commit();
            return $newRoleId;
        } catch (RuntimeException $e) {
            $this->conn->rollBack();
            error_log('Role::cloneRole - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of users assigned to a role.
     *
     * @param int $roleId
     * @return int
     */
    public function getUsersCount(int $roleId): int
    {
        try {
            $sql = "SELECT COUNT(*) AS total FROM users WHERE role_id = :role_id AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        } catch (RuntimeException $e) {
            error_log('Role::getUsersCount - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all roles with user count.
     *
     * @return array
     */
    public function getWithUserCount(): array
    {
        try {
            $sql = "SELECT r.*, COUNT(u.id) AS user_count
                    FROM roles r
                    LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL
                    GROUP BY r.id
                    ORDER BY r.name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Role::getWithUserCount - ' . $e->getMessage());
            return [];
        }
    }
}
