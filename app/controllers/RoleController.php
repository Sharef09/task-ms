<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Role;
use App\Models\Permission;

class RoleController
{
    private Database $db;
    private Session $session;
    private Role $roleModel;
    private Permission $permissionModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $roles = $this->roleModel->getWithUserCount();

            $pageTitle = 'Roles';
            $content = __DIR__ . '/../views/roles/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Role index error', $e);
            flash('error', 'Unable to load roles');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $pageTitle = 'Create Role';
            $content = __DIR__ . '/../views/roles/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Role create form error', $e);
            flash('error', 'Unable to load form');
            redirect('roles');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('roles/create');
                return;
            }

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                flash('error', 'Role name is required');
                redirect('roles/create');
                return;
            }

            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

            $existing = $this->db->fetch("SELECT id FROM roles WHERE slug = ?", [$slug]);
            if ($existing) {
                flash('error', 'Role with this name already exists');
                redirect('roles/create');
                return;
            }

            $roleId = $this->roleModel->create([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_system' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($roleId) {
                logActivity('Create Role', 'Roles', $roleId, null, json_encode(['name' => $name]));
                flash('success', 'Role created successfully');
            } else {
                flash('error', 'Failed to create role');
            }

            redirect('roles');

        } catch (\Throwable $e) {
            logError('Role store error', $e);
            flash('error', 'Failed to create role');
            redirect('roles/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $role = $this->roleModel->getById($id);
            if (!$role) {
                flash('error', 'Role not found');
                redirect('roles');
                return;
            }

            $pageTitle = 'Edit Role';
            $content = __DIR__ . '/../views/roles/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Role edit form error', $e);
            flash('error', 'Unable to load form');
            redirect('roles');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('roles/edit/' . $id);
                return;
            }

            $role = $this->roleModel->getById($id);
            if (!$role) {
                flash('error', 'Role not found');
                redirect('roles');
                return;
            }

            if ($role->is_system) {
                flash('error', 'System roles cannot be modified');
                redirect('roles');
                return;
            }

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                flash('error', 'Role name is required');
                redirect('roles/edit/' . $id);
                return;
            }

            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $existing = $this->db->fetch("SELECT id FROM roles WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existing) {
                flash('error', 'Another role with this name already exists');
                redirect('roles/edit/' . $id);
                return;
            }

            $this->roleModel->update($id, [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
            ]);

            logActivity('Update Role', 'Roles', $id, null, json_encode(['name' => $name]));
            flash('success', 'Role updated successfully');
            redirect('roles');

        } catch (\Throwable $e) {
            logError('Role update error', $e);
            flash('error', 'Failed to update role');
            redirect('roles/edit/' . $id);
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $role = $this->roleModel->getById($id);
            if (!$role) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Role not found']);
                return;
            }

            if ($role->is_system) {
                echo json_encode(['success' => false, 'message' => 'System roles cannot be deleted']);
                return;
            }

            $userCount = $this->roleModel->getUsersCount($id);
            if ($userCount > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete role with assigned users']);
                return;
            }

            $this->db->delete('role_permissions', 'role_id = ?', [$id]);
            $this->roleModel->delete($id);
            logActivity('Delete Role', 'Roles', $id);

            echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);

        } catch (\Throwable $e) {
            logError('Role delete error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete role']);
        }
    }

    public function clone(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('roles');
                return;
            }

            $role = $this->roleModel->getById($id);
            if (!$role) {
                flash('error', 'Role not found');
                redirect('roles');
                return;
            }

            $newName = trim($_POST['name'] ?? $role->name . ' (Clone)');
            $newRoleId = $this->roleModel->cloneRole($id, $newName);

            if ($newRoleId) {
                logActivity('Clone Role', 'Roles', $newRoleId, null, json_encode(['source_role_id' => $id, 'new_name' => $newName]));
                flash('success', 'Role cloned successfully');
            } else {
                flash('error', 'Failed to clone role');
            }

            redirect('roles');

        } catch (\Throwable $e) {
            logError('Role clone error', $e);
            flash('error', 'Failed to clone role');
            redirect('roles');
        }
    }

    public function permissions(int $id): void
    {
        try {
            $role = $this->roleModel->getById($id);
            if (!$role) {
                flash('error', 'Role not found');
                redirect('roles');
                return;
            }

            $allPermissions = $this->permissionModel->getAll();
            $rolePermissions = $this->roleModel->getPermissions($id);
            $rolePermIds = array_map(fn($p) => $p->id, $rolePermissions);
            $modules = $this->permissionModel->getModules();

            $pageTitle = 'Role Permissions';
            $content = __DIR__ . '/../views/roles/permissions.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Role permissions form error', $e);
            flash('error', 'Unable to load permissions');
            redirect('roles');
        }
    }

    public function updatePermissions(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $role = $this->roleModel->getById($id);
            if (!$role) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Role not found']);
                return;
            }

            $permissionIds = array_map('intval', $_POST['permissions'] ?? []);
            $success = $this->roleModel->setPermissions($id, $permissionIds);

            if ($success) {
                logActivity('Update Permissions', 'Roles', $id, null, json_encode(['permission_ids' => $permissionIds]));
                echo json_encode(['success' => true, 'message' => 'Permissions updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update permissions']);
            }

        } catch (\Throwable $e) {
            logError('Role update permissions error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update permissions']);
        }
    }
}
