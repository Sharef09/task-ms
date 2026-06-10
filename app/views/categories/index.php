<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-tags me-2"></i>Task Category Management</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/categories/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Add Category
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Category Name</th>
                        <th>Color</th>
                        <th>Description</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="badge" style="background-color:<?= e($cat->color ?? '#2563eb') ?>; color:#fff;">
                                    <?= e($cat->name) ?>
                                </span>
                            </td>
                            <td>
                                <span class="d-inline-block rounded border" style="width:24px; height:24px; background:<?= e($cat->color ?? '#2563eb') ?>;"></span>
                                <code class="ms-1 small"><?= e($cat->color ?? '#2563eb') ?></code>
                            </td>
                            <td class="text-truncate" style="max-width:350px;"><?= e($cat->description ?? '-') ?></td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/categories/edit/<?= e($cat->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-delete-cat="<?= e($cat->id) ?>" data-name="<?= e($cat->name) ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                No categories found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" id="deleteCatForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<div class="modal fade" id="deleteCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete category <strong id="deleteCatName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteCatBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteCatId = null;
    document.querySelectorAll('[data-delete-cat]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteCatId = this.getAttribute('data-delete-cat');
            document.getElementById('deleteCatName').textContent = this.getAttribute('data-name');
            new bootstrap.Modal(document.getElementById('deleteCatModal')).show();
        });
    });
    document.getElementById('confirmDeleteCatBtn').addEventListener('click', function() {
        if (deleteCatId) {
            const form = document.getElementById('deleteCatForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/categories/delete/' + deleteCatId;
            form.submit();
        }
    });
});
</script>
