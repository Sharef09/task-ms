<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Category</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/categories" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/categories/update/<?= e($category->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-medium">Category Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Bug Fix" value="<?= e(old('name', $category->name)) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="color" class="form-label small fw-medium">Color</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-palette"></i></span>
                        <input type="color" class="form-control form-control-color" id="color" name="color" value="<?= e(old('color', $category->color ?? '#2563eb')) ?>" style="padding:2px;">
                    </div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter category description"><?= e(old('description', $category->description ?? '')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Category</button>
                <a href="<?= rtrim($app['url'], '/') ?>/categories" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
