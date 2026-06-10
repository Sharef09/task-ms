<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Permission
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all permissions.
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM permissions ORDER BY module, name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Permission::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single permission by ID.
     *
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT * FROM permissions WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Permission::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get permissions by module name.
     *
     * @param string $module
     * @return array
     */
    public function getByModule(string $module): array
    {
        try {
            $sql = "SELECT * FROM permissions WHERE module = :module ORDER BY name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':module', $module);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Permission::getByModule - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct permission modules.
     *
     * @return array
     */
    public function getModules(): array
    {
        try {
            $sql = "SELECT DISTINCT module FROM permissions ORDER BY module ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Permission::getModules - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new permission.
     *
     * @param array $data
     * @return int The inserted permission ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('permissions', $data);
        } catch (RuntimeException $e) {
            error_log('Permission::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing permission.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('permissions', $data, 'id = ?', [$id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a permission by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('permissions', 'id = :id', ['id' => $id]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get permissions assigned to a role.
     *
     * @param int $roleId
     * @return array
     */
    public function getRolePermissions(int $roleId): array
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
            error_log('Permission::getRolePermissions - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get permissions assigned directly to a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserPermissions(int $userId): array
    {
        try {
            $sql = "SELECT p.*, up.granted
                    FROM permissions p
                    INNER JOIN user_permissions up ON up.permission_id = p.id
                    WHERE up.user_id = :user_id
                    ORDER BY p.module, p.name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Permission::getUserPermissions - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get effective permissions for a user (role-based + directly assigned).
     *
     * @param int $userId
     * @return array
     */
    public function getUserEffectivePermissions(int $userId): array
    {
        try {
            $sql = "SELECT DISTINCT p.*
                    FROM permissions p
                    LEFT JOIN role_permissions rp ON rp.permission_id = p.id
                    LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = :user_id
                    WHERE rp.role_id = (SELECT role_id FROM users WHERE id = :user_id2)
                       OR (up.user_id = :user_id3 AND up.granted = 1)
                    ORDER BY p.module, p.name";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id3', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Permission::getUserEffectivePermissions - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign a permission to a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function setRolePermission(int $roleId, int $permissionId): bool
    {
        try {
            $db = Database::getInstance();
            $db->insert('role_permissions', [
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::setRolePermission - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a permission from a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function removeRolePermission(int $roleId, int $permissionId): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('role_permissions', 'role_id = :role_id AND permission_id = :permission_id', [
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::removeRolePermission - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set a direct user permission override (grant).
     *
     * @param int $userId
     * @param int $permissionId
     * @return bool
     */
    public function setUserPermission(int $userId, int $permissionId): bool
    {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO user_permissions (user_id, permission_id, granted)
                    VALUES (:user_id, :permission_id, 1)
                    ON DUPLICATE KEY UPDATE granted = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':permission_id', $permissionId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::setUserPermission - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a direct user permission override (revert to role inheritance).
     *
     * @param int $userId
     * @param int $permissionId
     * @return bool
     */
    public function removeUserPermission(int $userId, int $permissionId): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('user_permissions', 'user_id = :uid AND permission_id = :pid', [
                'uid' => $userId,
                'pid' => $permissionId,
            ]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::removeUserPermission - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch-set role permissions (replaces all existing).
     *
     * @param int   $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function setRolePermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('role_permissions', 'role_id = :rid', ['rid' => $roleId]);
            foreach ($permissionIds as $pid) {
                $db->insert('role_permissions', [
                    'role_id'       => $roleId,
                    'permission_id' => (int) $pid,
                ]);
            }
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::setRolePermissions - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch-set user permissions (replaces all existing direct grants).
     *
     * @param int   $userId
     * @param array $permissionIds
     * @return bool
     */
    public function setUserPermissions(int $userId, array $permissionIds): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('user_permissions', 'user_id = :uid', ['uid' => $userId]);
            foreach ($permissionIds as $pid) {
                $db->insert('user_permissions', [
                    'user_id'       => $userId,
                    'permission_id' => (int) $pid,
                    'granted'       => 1,
                ]);
            }
            return true;
        } catch (RuntimeException $e) {
            error_log('Permission::setUserPermissions - ' . $e->getMessage());
            return false;
        }
    }
}
