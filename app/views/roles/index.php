<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2"></i>Role Management</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/roles/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Add Role
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Role Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-medium"><?= e($role->name) ?></div>
                                <?php if (!empty($role->is_system)): ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px;">System</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($role->slug) ?></code></td>
                            <td class="text-truncate" style="max-width:250px;"><?= e($role->description ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= ($role->user_count ?? 0) > 0 ? 'primary' : 'light text-muted' ?> bg-opacity-10">
                                    <?= e($role->user_count ?? 0) ?> users
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/roles/edit/<?= e($role->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/roles/clone/<?= e($role->id) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Clone Role" onclick="return confirm('Clone role <?= e($role->name) ?>?')"><i class="fas fa-copy"></i></button>
                                    </form>
                                    <?php if (empty($role->is_system)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" <?= ($role->user_count ?? 0) > 0 ? 'disabled' : '' ?> data-delete-role="<?= e($role->id) ?>" data-name="<?= e($role->name) ?>" data-has-users="<?= ($role->user_count ?? 0) > 0 ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>
                                No roles found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" id="deleteRoleForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete <strong id="deleteRoleName"></strong>? This action cannot be undone.</p>
                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small d-none" id="roleHasUsersAlert">
                    <i class="fas fa-exclamation-triangle me-1"></i>This role has assigned users and cannot be deleted.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteRoleBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteRoleId = null;
    document.querySelectorAll('[data-delete-role]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteRoleId = this.getAttribute('data-delete-role');
            const hasUsers = this.getAttribute('data-has-users') === '1';
            document.getElementById('deleteRoleName').textContent = this.getAttribute('data-name');
            document.getElementById('roleHasUsersAlert').classList.toggle('d-none', !hasUsers);
            document.getElementById('confirmDeleteRoleBtn').disabled = hasUsers;
            new bootstrap.Modal(document.getElementById('deleteRoleModal')).show();
        });
    });

    document.getElementById('confirmDeleteRoleBtn').addEventListener('click', function() {
        if (deleteRoleId) {
            const form = document.getElementById('deleteRoleForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/roles/delete/' + deleteRoleId;
            form.submit();
        }
    });
});
</script>
