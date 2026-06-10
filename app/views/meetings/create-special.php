<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-file-pen me-2"></i>Request Special Meeting</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/meetings/special-requests" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <p class="small text-muted mb-3">Submit a request for a special meeting. An administrator or manager will review and approve it.</p>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/special-requests/store">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-medium">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" value="<?= e(old('title')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= e($dept->id) ?>" <?= old('department_id') == $dept->id ? 'selected' : '' ?>><?= e($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Preferred Date</label>
                    <input type="datetime-local" class="form-control" name="preferred_date" value="<?= e(old('preferred_date')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= e(old('description')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i>Submit Request</button>
                <a href="<?= rtrim($app['url'], '/') ?>/meetings/special-requests" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
