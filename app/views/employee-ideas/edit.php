<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$categories = ['Improvement', 'Innovation', 'Efficiency', 'Cost Saving', 'Safety', 'Other'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Idea</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/employee-ideas/update/<?= e($idea->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label for="title" class="form-label small fw-medium">Idea Title <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-heading"></i></span>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter your idea title" value="<?= e(old('title', $idea->title)) ?>" required maxlength="255">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="category" class="form-label small fw-medium">Category</label>
                    <select class="form-select" id="category" name="category">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c) ?>" <?= (old('category', $idea->category) === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Describe your idea in detail"><?= e(old('description', $idea->description ?? '')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Idea</button>
                <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas/view/<?= e($idea->id) ?>" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
