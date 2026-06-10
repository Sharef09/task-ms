<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';

$allActions = [];
$actionOrder = ['view', 'add', 'edit', 'delete', 'assign', 'approve', 'export', 'print', 'backup', 'restore', 'manage_permissions'];
foreach ($permissions as $p) {
    $parts = explode('.', $p->slug);
    $action = end($parts);
    if (!in_array($action, $allActions)) {
        $allActions[] = $action;
    }
}
$sortedActions = [];
foreach ($actionOrder as $ao) {
    if (in_array($ao, $allActions)) {
        $sortedActions[] = $ao;
    }
}
foreach ($allActions as $a) {
    if (!in_array($a, $sortedActions)) {
        $sortedActions[] = $a;
    }
}
$actions = $sortedActions;

$actionLabels = [
    'view'               => 'View',
    'add'                => 'Add',
    'edit'               => 'Edit',
    'delete'             => 'Delete',
    'assign'             => 'Assign',
    'approve'            => 'Approve',
    'export'             => 'Export',
    'print'              => 'Print',
    'backup'             => 'Backup',
    'restore'            => 'Restore',
    'manage_permissions' => 'Manage Permissions',
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-key me-2"></i>Permission Management</h5>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $selectedType === 'user' ? 'active' : '' ?> border-0 rounded-0 py-3 px-3 fw-medium small" id="users-tab" data-bs-toggle="tab" data-bs-target="#usersPanel" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $selectedType === 'role' ? 'active' : '' ?> border-0 rounded-0 py-3 px-3 fw-medium small" id="roles-tab" data-bs-toggle="tab" data-bs-target="#rolesPanel" type="button" role="tab">
                            <i class="fas fa-shield-alt me-1"></i>Roles
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade <?= $selectedType === 'user' ? 'show active' : '' ?>" id="usersPanel" role="tabpanel">
                    <div class="list-group list-group-flush" style="max-height:500px; overflow-y:auto;">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <a href="<?= rtrim($app['url'], '/') ?>/permissions?type=user&id=<?= e($user->id) ?>"
                               class="list-group-item list-group-item-action border-bottom d-flex align-items-center gap-2 px-3 py-2 <?= ($selectedType === 'user' && $selectedId == $user->id) ? 'active' : '' ?>">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px;">
                                    <i class="fas fa-user <?= ($selectedType === 'user' && $selectedId == $user->id) ? 'text-white' : 'text-muted' ?> small"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="small fw-medium text-truncate"><?= e($user->first_name . ' ' . $user->last_name) ?></div>
                                    <div class="small" style="font-size:11px; opacity:0.75;"><?= e($user->email ?? '') ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 small">No users found</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tab-pane fade <?= $selectedType === 'role' ? 'show active' : '' ?>" id="rolesPanel" role="tabpanel">
                    <div class="list-group list-group-flush" style="max-height:500px; overflow-y:auto;">
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $role): ?>
                            <a href="<?= rtrim($app['url'], '/') ?>/permissions?type=role&id=<?= e($role->id) ?>"
                               class="list-group-item list-group-item-action border-bottom d-flex align-items-center gap-2 px-3 py-2 <?= ($selectedType === 'role' && $selectedId == $role->id) ? 'active' : '' ?>">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px;">
                                    <i class="fas fa-shield-alt <?= ($selectedType === 'role' && $selectedId == $role->id) ? 'text-white' : 'text-muted' ?> small"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="small fw-medium text-truncate"><?= e($role->name) ?></div>
                                    <div class="small" style="font-size:11px; opacity:0.75;"><code><?= e($role->slug) ?></code></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 small">No roles found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (!$selectedId): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-hand-pointer fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted mb-0">Select a user or role from the left panel to manage permissions.</p>
            </div>
        </div>
        <?php else: ?>
        <?php
        $entityName = '';
        if ($selectedType === 'user') {
            foreach ($users as $u) {
                if ($u->id == $selectedId) { $entityName = $u->first_name . ' ' . $u->last_name; break; }
            }
        } else {
            foreach ($roles as $r) {
                if ($r->id == $selectedId) { $entityName = $r->name; break; }
            }
        }
        ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="fw-bold mb-0"><i class="fas fa-<?= $selectedType === 'role' ? 'shield-alt' : 'user' ?> me-2"></i>Permissions for <?= e($entityName) ?></h6>
                    <?php if ($selectedType === 'user' && $inheritedRoleName): ?>
                    <div class="small text-muted mt-1">
                        <i class="fas fa-arrow-right text-primary me-1"></i>
                        Inherits from role: <strong><?= e($inheritedRoleName) ?></strong>
                        <span class="text-muted">(permissions with <i class="fas fa-link text-info" style="font-size:10px;"></i> icon come from role)</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" id="savePermissionsBtn"><i class="fas fa-save me-1"></i>Save</button>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cloneModal"><i class="fas fa-copy me-1"></i>Clone</button>
                        <a href="<?= rtrim($app['url'], '/') ?>/permissions?type=<?= e($selectedType) ?>&id=<?= e($selectedId) ?>" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Cancel</a>
                    </div>
                    <div class="small text-muted">
                        <span id="selectedCount">0</span> / <span id="totalCount">0</span> selected
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 small" id="permissionTable">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-medium text-nowrap" style="min-width:140px;">Module</th>
                                <?php foreach ($actions as $actionKey): ?>
                                <th class="text-center fw-medium text-nowrap px-2">
                                    <?= e($actionLabels[$actionKey] ?? ucfirst($actionKey)) ?>
                                    <br>
                                    <input type="checkbox" class="column-toggle-all" data-action="<?= e($actionKey) ?>" title="Select All">
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($permissionsByModule)): ?>
                                <?php foreach ($permissionsByModule as $moduleKey => $modulePerms): ?>
                                <tr>
                                    <td class="fw-medium text-nowrap"><?= e(ucfirst($moduleKey)) ?></td>
                                    <?php foreach ($actions as $actionKey): ?>
                                    <?php
                                    $perm = null;
                                    foreach ($modulePerms as $p) {
                                        $pAction = explode('.', $p->slug);
                                        $pAction = end($pAction);
                                        if ($pAction === $actionKey) {
                                            $perm = $p;
                                            break;
                                        }
                                    }
                                    $checked = $perm && in_array($perm->id, $selectedPermissions);
                                    $inherited = $selectedType === 'user' && $perm && in_array($perm->id, $inheritedPermissionIds) && !$checked;
                                    ?>
                                    <td class="text-center px-2">
                                        <?php if ($perm): ?>
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="checkbox"
                                                   class="permission-checkbox"
                                                   data-type="<?= e($selectedType) ?>"
                                                   data-entity-id="<?= e($selectedId) ?>"
                                                   data-permission-id="<?= e($perm->id) ?>"
                                                   data-action="<?= e($actionKey) ?>"
                                                   data-module="<?= e($moduleKey) ?>"
                                                <?= $checked ? 'checked' : '' ?>>
                                            <?php if ($inherited): ?>
                                            <i class="fas fa-link text-info" style="font-size:9px;" title="Inherited from role: <?= e($inheritedRoleName) ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted" style="opacity:0.3;"><i class="fas fa-minus"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= count($actions) + 1 ?>" class="text-center text-muted py-4">No permission modules defined.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clone Modal -->
<div class="modal fade" id="cloneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fas fa-copy me-2"></i>Clone Permissions</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cloneTargetType" value="<?= e($selectedType) ?>">
                <input type="hidden" id="cloneTargetId" value="<?= e($selectedId) ?>">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Copy permissions from</label>
                    <select class="form-select form-select-sm" id="cloneSourceType">
                        <option value="role">Role</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="mb-2" id="cloneSourceRoleGroup">
                    <label class="form-label small fw-medium">Select Role</label>
                    <select class="form-select form-select-sm" id="cloneSourceRole">
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role->id) ?>"><?= e($role->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2 d-none" id="cloneSourceUserGroup">
                    <label class="form-label small fw-medium">Select User</label>
                    <select class="form-select form-select-sm" id="cloneSourceUser">
                        <?php foreach ($users as $user): ?>
                        <option value="<?= e($user->id) ?>"><?= e($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="confirmCloneBtn"><i class="fas fa-copy me-1"></i> Clone</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalCheckboxes = document.querySelectorAll('.permission-checkbox').length;
    document.getElementById('totalCount').textContent = totalCheckboxes;

    function updateCounts() {
        const checked = document.querySelectorAll('.permission-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
    }

    // Column toggle all (local only, no save)
    document.querySelectorAll('.column-toggle-all').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const action = this.getAttribute('data-action');
            const checked = this.checked;
            document.querySelectorAll('.permission-checkbox[data-action="' + action + '"]').forEach(function(pcb) {
                pcb.checked = checked;
            });
            updateCounts();
            updateColumnToggleStates();
        });
    });

    // Individual checkbox change (local only, no save)
    document.querySelectorAll('.permission-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateCounts();
            updateColumnToggleStates();
        });
    });

    // Save button - batch save all checked permissions
    document.getElementById('savePermissionsBtn').addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.permission-checkbox:checked');
        const permissionIds = Array.from(checkedBoxes).map(function(cb) {
            return cb.getAttribute('data-permission-id');
        });
        const type = '<?= e($selectedType) ?>';
        const entityId = '<?= e($selectedId) ?>';

        const formData = new FormData();
        formData.append('_csrf_token', '<?= csrf_token() ?>');
        formData.append('type', type);
        formData.append('entity_id', entityId);
        permissionIds.forEach(function(id) {
            formData.append('permission_ids[]', id);
        });

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        fetch('<?= rtrim($app['url'], '/') ?>/permissions/save', {
            method: 'POST',
            body: formData,
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to save permissions');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
            }
        })
        .catch(function() {
            alert('An error occurred while saving.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
        });
    });

    // Clone modal - toggle source type
    document.getElementById('cloneSourceType').addEventListener('change', function() {
        const val = this.value;
        document.getElementById('cloneSourceRoleGroup').classList.toggle('d-none', val !== 'role');
        document.getElementById('cloneSourceUserGroup').classList.toggle('d-none', val !== 'user');
    });

    // Clone button
    document.getElementById('confirmCloneBtn').addEventListener('click', function() {
        const targetType = document.getElementById('cloneTargetType').value;
        const targetId = document.getElementById('cloneTargetId').value;
        const sourceType = document.getElementById('cloneSourceType').value;

        let sourceId;
        if (sourceType === 'role') {
            sourceId = document.getElementById('cloneSourceRole').value;
        } else {
            sourceId = document.getElementById('cloneSourceUser').value;
        }

        const formData = new FormData();
        formData.append('_csrf_token', '<?= csrf_token() ?>');
        formData.append('target_type', targetType);
        formData.append('target_id', targetId);
        formData.append('source_type', sourceType);
        formData.append('source_id', sourceId);

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cloning...';

        fetch('<?= rtrim($app['url'], '/') ?>/permissions/clone', {
            method: 'POST',
            body: formData,
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to clone permissions');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-copy me-1"></i> Clone';
            }
        })
        .catch(function() {
            alert('An error occurred while cloning.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-copy me-1"></i> Clone';
        });
    });

    function updateColumnToggleStates() {
        document.querySelectorAll('.column-toggle-all').forEach(function(cb) {
            const action = cb.getAttribute('data-action');
            const checkboxes = document.querySelectorAll('.permission-checkbox[data-action="' + action + '"]');
            if (checkboxes.length === 0) {
                cb.checked = false;
                cb.indeterminate = false;
                return;
            }
            const checkedCount = document.querySelectorAll('.permission-checkbox[data-action="' + action + '"]:checked').length;
            if (checkedCount === checkboxes.length) {
                cb.checked = true;
                cb.indeterminate = false;
            } else if (checkedCount === 0) {
                cb.checked = false;
                cb.indeterminate = false;
            } else {
                cb.checked = false;
                cb.indeterminate = true;
            }
        });
    }

    updateCounts();
    updateColumnToggleStates();
});
</script>
