<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-building me-2"></i>Department Management</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/departments/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Add Department
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Department Name</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-medium"><?= e($dept->name) ?></div>
                            </td>
                            <td class="text-truncate" style="max-width:350px;"><?= e($dept->description ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= ($dept->user_count ?? 0) > 0 ? 'primary' : 'light text-muted' ?> bg-opacity-10">
                                    <?= e($dept->user_count ?? 0) ?> users
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/departments/edit/<?= e($dept->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-delete-dept="<?= e($dept->id) ?>" data-name="<?= e($dept->name) ?>" data-has-users="<?= ($dept->user_count ?? 0) > 0 ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                No departments found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" id="deleteDeptForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<div class="modal fade" id="deleteDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete <strong id="deleteDeptName"></strong>? This action cannot be undone.</p>
                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small d-none" id="deptHasUsersAlert">
                    <i class="fas fa-exclamation-triangle me-1"></i>This department has assigned users and cannot be deleted.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteDeptBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteDeptId = null;
    document.querySelectorAll('[data-delete-dept]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteDeptId = this.getAttribute('data-delete-dept');
            const hasUsers = this.getAttribute('data-has-users') === '1';
            document.getElementById('deleteDeptName').textContent = this.getAttribute('data-name');
            document.getElementById('deptHasUsersAlert').classList.toggle('d-none', !hasUsers);
            document.getElementById('confirmDeleteDeptBtn').disabled = hasUsers;
            new bootstrap.Modal(document.getElementById('deleteDeptModal')).show();
        });
    });
    document.getElementById('confirmDeleteDeptBtn').addEventListener('click', function() {
        if (deleteDeptId) {
            const form = document.getElementById('deleteDeptForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/departments/delete/' + deleteDeptId;
            form.submit();
        }
    });
});
</script>
