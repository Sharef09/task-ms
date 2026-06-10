<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Department</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/departments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/departments/update/<?= e($department->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-medium">Department Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Human Resources" value="<?= e(old('name', $department->name)) ?>" required>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter department description"><?= e(old('description', $department->description ?? '')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Department</button>
                <a href="<?= rtrim($app['url'], '/') ?>/departments" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
