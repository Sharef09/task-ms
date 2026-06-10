<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Role</h5>
    <div class="d-flex gap-1">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/roles/clone/<?= e($role->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('Clone role <?= e($role->name) ?>?')"><i class="fas fa-copy me-1"></i>Clone Role</button>
        </form>
        <a href="<?= rtrim($app['url'], '/') ?>/roles" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/roles/update/<?= e($role->id) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-medium">Role Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Project Manager" value="<?= e(old('name', $role->name)) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label small fw-medium">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= e(old('slug', $role->slug)) ?>" readonly>
                    </div>
                    <div class="form-text small text-muted">Auto-generated from the role name.</div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter role description"><?= e(old('description', $role->description ?? '')) ?></textarea>
                </div>
            </div>

            <?php if (!empty($role->is_system)): ?>
            <div class="alert alert-warning py-2 mt-3 mb-0 small">
                <i class="fas fa-info-circle me-1"></i>This is a system role. Some properties may be restricted.
            </div>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Role</button>
                <a href="<?= rtrim($app['url'], '/') ?>/roles" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    nameInput.addEventListener('input', function() {
        slugInput.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
});
</script>
