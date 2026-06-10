<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Task: <?= e($task->task_number) ?></h5>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php include __DIR__ . '/_nav.php' ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/update/<?= e($task->id) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="title" class="form-label small fw-medium">Title <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-heading"></i></span>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter task title" value="<?= e(old('title', $task->title)) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="category_id" class="form-label small fw-medium">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat->id) ?>" <?= (old('category_id', $task->category_id ?? '') == $cat->id) ? 'selected' : '' ?>><?= e($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="department_id" class="form-label small fw-medium">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= e($dept->id) ?>" <?= (old('department_id', $task->department_id ?? '') == $dept->id) ? 'selected' : '' ?>><?= e($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="assigned_to" class="form-label small fw-medium">Assigned To</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= e($user->id) ?>" <?= (old('assigned_to', $task->assigned_to ?? '') == $user->id) ? 'selected' : '' ?>><?= e($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="task_type" class="form-label small fw-medium">Task Type</label>
                    <select class="form-select" id="task_type" name="task_type" onchange="toggleAttachment(this)">
                        <option value="Normal" <?= (old('task_type', $task->task_type ?? 'Normal') === 'Normal') ? 'selected' : '' ?>>Normal</option>
                        <option value="File Attached" <?= (old('task_type', $task->task_type ?? 'Normal') === 'File Attached') ? 'selected' : '' ?>>File Attached</option>
                        <option value="Initiation" <?= (old('task_type', $task->task_type ?? 'Normal') === 'Initiation') ? 'selected' : '' ?>>Initiation</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority" class="form-label small fw-medium">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <?php foreach ($priorities as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= (old('priority', $task->priority ?? '') === $priority) ? 'selected' : '' ?>><?= e($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label small fw-medium">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= (old('status', $task->status ?? '') === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="progress" class="form-label small fw-medium">Progress Percentage</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                        <input type="number" class="form-control" id="progress" name="progress" placeholder="0" value="<?= e(old('progress', $task->progress ?? 0)) ?>" min="0" max="100" step="1">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="start_date" class="form-label small fw-medium">Start Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e(old('start_date', $task->start_date ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="due_date" class="form-label small fw-medium">Due Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= e(old('due_date', $task->due_date ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="estimated_hours" class="form-label small fw-medium">Estimated Hours</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                        <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" placeholder="0" value="<?= e(old('estimated_hours', $task->estimated_hours ?? '')) ?>" min="0" step="0.5">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="actual_hours" class="form-label small fw-medium">Actual Hours</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                        <input type="number" class="form-control" id="actual_hours" name="actual_hours" placeholder="0" value="<?= e(old('actual_hours', $task->actual_hours ?? '')) ?>" min="0" step="0.5">
                    </div>
                </div>

                <div class="col-md-6" id="attachmentField" style="<?= (($task->task_type ?? 'Normal') !== 'Normal') ? '' : 'display:none;' ?>">
                    <label for="attachment" class="form-label small fw-medium">Attachment</label>
                    <input type="file" class="form-control" id="attachment" name="attachment">
                    <div class="form-text small">Leave empty to keep current attachment.</div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label small fw-medium">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Enter task description"><?= e(old('description', $task->description ?? '')) ?></textarea>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Task</button>
                <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAttachment(el) {
    var f = document.getElementById('attachmentField');
    if (f) f.style.display = el.value !== 'Normal' ? '' : 'none';
}
</script>
