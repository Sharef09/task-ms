<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class PermissionController
{
    private Database $db;
    private Session $session;
    private Permission $permissionModel;
    private Role $roleModel;
    private User $userModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->permissionModel = new Permission();
        $this->roleModel = new Role();
        $this->userModel = new User();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $permissions = $this->permissionModel->getAll();
            $modules = $this->permissionModel->getModules();
            $roles = $this->roleModel->getAll();
            $users = $this->userModel->getActive();

            $permissionsByModule = [];
            foreach ($modules as $module) {
                $permissionsByModule[$module->module] = $this->permissionModel->getByModule($module->module);
            }

            $rolePermissions = [];
            foreach ($roles as $role) {
                $rolePermissions[$role->id] = array_map(
                    fn($p) => $p->id,
                    $this->permissionModel->getRolePermissions($role->id)
                );
            }

            $selectedType = in_array($_GET['type'] ?? '', ['role', 'user']) ? $_GET['type'] : 'role';
            $selectedId = (int) ($_GET['id'] ?? 0);

            $selectedPermissions = [];
            $inheritedRoleName = null;
            $inheritedPermissionIds = [];

            if ($selectedType === 'role' && $selectedId > 0) {
                $selectedPermissions = $rolePermissions[$selectedId] ?? [];
            } elseif ($selectedType === 'user' && $selectedId > 0) {
                $userPerms = $this->permissionModel->getUserPermissions($selectedId);
                foreach ($userPerms as $up) {
                    if ($up->granted) {
                        $selectedPermissions[] = $up->id;
                    }
                }
                $user = $this->userModel->getById($selectedId);
                if ($user && !empty($user->role_id)) {
                    $inheritedRoleName = $user->role_name ?? null;
                    $inheritedPermissionIds = $rolePermissions[$user->role_id] ?? [];
                }
            }

            $pageTitle = 'Permissions';
            $content = __DIR__ . '/../views/permissions/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Permission index error', $e);
            flash('error', 'Unable to load permissions');
            redirect('dashboard');
        }
    }

    public function updateRolePermission(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionId = (int) ($_POST['permission_id'] ?? 0);
            $granted = ($_POST['granted'] ?? '') === 'true';

            $role = $this->roleModel->getById($roleId);
            if (!$role) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Role not found']);
                return;
            }

            if ($granted) {
                $this->permissionModel->setRolePermission($roleId, $permissionId);
            } else {
                $this->permissionModel->removeRolePermission($roleId, $permissionId);
            }

            logActivity('Update Role Permission', 'Permissions', $roleId, null, json_encode([
                'permission_id' => $permissionId,
                'granted' => $granted,
            ]));

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            logError('Update role permission error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update permission']);
        }
    }

    public function updateUserPermission(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $userId = (int) ($_POST['user_id'] ?? 0);
            $permissionId = (int) ($_POST['permission_id'] ?? 0);
            $granted = ($_POST['granted'] ?? '') === 'true';

            $user = $this->userModel->getById($userId);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            if ($granted) {
                $this->permissionModel->setUserPermission($userId, $permissionId);
            } else {
                $this->permissionModel->removeUserPermission($userId, $permissionId);
            }

            logActivity('Update User Permission', 'Permissions', $userId, null, json_encode([
                'permission_id' => $permissionId,
                'granted' => $granted,
            ]));

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            logError('Update user permission error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update permission']);
        }
    }

    public function getRolePermissions(int $roleId): void
    {
        try {
            $role = $this->roleModel->getById($roleId);
            if (!$role) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Role not found']);
                return;
            }

            $permissions = $this->permissionModel->getRolePermissions($roleId);
            $ids = array_map(fn($p) => $p->id, $permissions);

            echo json_encode(['success' => true, 'data' => $ids]);

        } catch (\Throwable $e) {
            logError('Get role permissions error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get permissions']);
        }
    }

    public function getUserPermissions(int $userId): void
    {
        try {
            $user = $this->userModel->getById($userId);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            $permissions = $this->permissionModel->getUserPermissions($userId);

            echo json_encode(['success' => true, 'data' => $permissions]);

        } catch (\Throwable $e) {
            logError('Get user permissions error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get permissions']);
        }
    }

    public function batchUpdate(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $type = $_POST['type'] ?? '';
            $entityId = (int) ($_POST['entity_id'] ?? 0);
            $permissionIds = isset($_POST['permission_ids']) && is_array($_POST['permission_ids'])
                ? array_map('intval', $_POST['permission_ids'])
                : [];

            if ($entityId < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid entity ID']);
                return;
            }

            if ($type === 'role') {
                $this->permissionModel->setRolePermissions($entityId, $permissionIds);
                logActivity('Batch update role permissions', 'Permissions', $entityId);
            } elseif ($type === 'user') {
                $this->permissionModel->setUserPermissions($entityId, $permissionIds);
                logActivity('Batch update user permissions', 'Permissions', $entityId);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                return;
            }

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            logError('Batch update permissions error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save permissions']);
        }
    }

    public function clonePermissions(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $targetType = $_POST['target_type'] ?? '';
            $targetId = (int) ($_POST['target_id'] ?? 0);
            $sourceType = $_POST['source_type'] ?? '';
            $sourceId = (int) ($_POST['source_id'] ?? 0);

            if ($targetId < 1 || $sourceId < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid IDs']);
                return;
            }

            // Get source permissions
            $sourcePermissionIds = [];
            if ($sourceType === 'role') {
                $sourcePerms = $this->permissionModel->getRolePermissions($sourceId);
                $sourcePermissionIds = array_map(fn($p) => $p->id, $sourcePerms);
            } elseif ($sourceType === 'user') {
                $sourcePerms = $this->permissionModel->getUserPermissions($sourceId);
                foreach ($sourcePerms as $p) {
                    if ($p->granted) {
                        $sourcePermissionIds[] = $p->id;
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid source type']);
                return;
            }

            // Apply to target
            if ($targetType === 'role') {
                $this->permissionModel->setRolePermissions($targetId, $sourcePermissionIds);
                logActivity('Clone permissions to role', 'Permissions', $targetId, null, json_encode([
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]));
            } elseif ($targetType === 'user') {
                $this->permissionModel->setUserPermissions($targetId, $sourcePermissionIds);
                logActivity('Clone permissions to user', 'Permissions', $targetId, null, json_encode([
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid target type']);
                return;
            }

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            logError('Clone permissions error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to clone permissions']);
        }
    }
}
