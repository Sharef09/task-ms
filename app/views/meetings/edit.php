<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Meeting</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/meetings" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/update/<?= e($meeting->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-medium">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" value="<?= e($meeting->title) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= $meeting->status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= e($dept->id) ?>" <?= $meeting->department_id == $dept->id ? 'selected' : '' ?>><?= e($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Organizer</label>
                    <select class="form-select" name="organizer_id">
                        <option value="">Select Organizer</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= e($u->id) ?>" <?= $meeting->organizer_id == $u->id ? 'selected' : '' ?>><?= e($u->first_name . ' ' . $u->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Location</label>
                    <input type="text" class="form-control" name="location" value="<?= e($meeting->location ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Start Date</label>
                    <input type="datetime-local" class="form-control" name="start_date" value="<?= e(str_replace(' ', 'T', $meeting->start_date ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">End Date</label>
                    <input type="datetime-local" class="form-control" name="end_date" value="<?= e(str_replace(' ', 'T', $meeting->end_date ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= e($meeting->description ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"><?= e($meeting->notes ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Meeting</button>
                <a href="<?= rtrim($app['url'], '/') ?>/meetings" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
