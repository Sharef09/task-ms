<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>User Management</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/users/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Add User
    </a>
</div>

<form method="GET" action="<?= rtrim($app['url'], '/') ?>/users" class="row g-2 mb-4">
    <div class="col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= e($filters['search'] ?? '') ?>">
        </div>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="department_id">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept->id) ?>" <?= (!empty($filters['department_id']) && $filters['department_id'] == $dept->id) ? 'selected' : '' ?>><?= e($dept->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="role_id">
            <option value="">All Roles</option>
            <?php foreach ($roles as $role): ?>
            <option value="<?= e($role->id) ?>" <?= (!empty($filters['role_id']) && $filters['role_id'] == $role->id) ? 'selected' : '' ?>><?= e($role->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="status">
            <option value="">All Status</option>
            <option value="active" <?= (!empty($filters['status']) && $filters['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (!empty($filters['status']) && $filters['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            <option value="suspended" <?= (!empty($filters['status']) && $filters['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
            <option value="locked" <?= (!empty($filters['status']) && $filters['status'] === 'locked') ? 'selected' : '' ?>>Locked</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-outline-primary flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
        <a href="<?= rtrim($app['url'], '/') ?>/users" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3"><input type="checkbox" id="selectAll"></th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="ps-3"><input type="checkbox" class="user-checkbox" value="<?= e($user->id) ?>"></td>
                            <td class="fw-medium"><?= e($user->employee_id ?? '-') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                        <i class="fas fa-user text-muted small"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium"><?= e($user->first_name . ' ' . $user->last_name) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($user->username) ?></td>
                            <td class="text-truncate" style="max-width:180px;"><?= e($user->email) ?></td>
                            <td><?= e($user->department_name ?? '-') ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info"><?= e($user->role_name ?? '-') ?></span></td>
                            <td><?= statusBadge(ucfirst($user->status)) ?></td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/users/view/<?= e($user->id) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="<?= rtrim($app['url'], '/') ?>/users/edit/<?= e($user->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-delete-user="<?= e($user->id) ?>" data-name="<?= e($user->first_name . ' ' . $user->last_name) ?>"><i class="fas fa-trash"></i></button>
                                    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/users/reset-password/<?= e($user->id) ?>" class="d-inline" onsubmit="return confirm('Reset password for <?= e($user->first_name) ?>?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset Password"><i class="fas fa-key"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                No users found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($users) && $totalPages > 1): ?>
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" style="width:auto;" id="bulkAction">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="applyBulkAction" disabled>Apply</button>
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/users?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '' ?><?= !empty($filters['role_id']) ? '&role_id=' . urlencode($filters['role_id']) : '' ?><?= !empty($filters['department_id']) ? '&department_id=' . urlencode($filters['department_id']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="deleteUserForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<form method="POST" id="bulkActionForm" action="<?= rtrim($app['url'], '/') ?>/users/bulk-update">
    <?= csrf_field() ?>
    <input type="hidden" name="user_ids" id="bulkUserIds">
    <input type="hidden" name="action" id="bulkActionType">
</form>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const applyBtn = document.getElementById('applyBulkAction');
    const bulkAction = document.getElementById('bulkAction');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleBulkBtn();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkBtn);
    });

    function toggleBulkBtn() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        applyBtn.disabled = checked.length === 0 || bulkAction.value === '';
    }

    bulkAction.addEventListener('change', toggleBulkBtn);

    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.user-checkbox:checked:not(#selectAll)');
            if (checked.length === 0) return;
            const ids = Array.from(checked).map(cb => cb.value).join(',');
            document.getElementById('bulkUserIds').value = ids;
            document.getElementById('bulkActionType').value = bulkAction.value;
            document.getElementById('bulkActionForm').submit();
        });
    }

    let deleteUserId = null;
    document.querySelectorAll('[data-delete-user]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteUserId = this.getAttribute('data-delete-user');
            document.getElementById('deleteUserName').textContent = this.getAttribute('data-name');
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        });
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteUserId) {
            const form = document.getElementById('deleteUserForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/users/delete/' + deleteUserId;
            form.submit();
        }
    });
});
</script>
