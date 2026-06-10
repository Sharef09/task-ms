<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0"><i class="fas fa-paper-plane me-2"></i>Sent Tasks</h5>
    <?php if (!isStaff()): ?>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Create Task
    </a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_nav.php' ?>

<form method="GET" action="<?= rtrim($app['url'], '/') ?>/tasks/sent-tasks" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search title..." value="<?= e($filters['search'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="department_id">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept->id) ?>" <?= (!empty($filters['department_id']) && $filters['department_id'] == $dept->id) ? 'selected' : '' ?>><?= e($dept->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="status">
            <option value="">All Status</option>
            <?php foreach ($statuses as $status): ?>
            <option value="<?= e($status) ?>" <?= (!empty($filters['status']) && $filters['status'] === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-1">
        <select class="form-select form-select-sm" name="priority">
            <option value="">All Priority</option>
            <?php foreach ($priorities as $priority): ?>
            <option value="<?= e($priority) ?>" <?= (!empty($filters['priority']) && $filters['priority'] === $priority) ? 'selected' : '' ?>><?= e($priority) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <div class="input-group input-group-sm">
            <input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
            <span class="input-group-text bg-white border-start-0 border-end-0"><i class="fas fa-minus text-muted"></i></span>
            <input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
    </div>
    <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter"></i></button>
        <a href="<?= rtrim($app['url'], '/') ?>/tasks/sent-tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Task Number</th>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="ps-3"><?= e($task->id) ?></td>
                            <td class="fw-medium text-nowrap"><?= e($task->task_number) ?></td>
                            <td><div class="text-truncate fw-medium" style="max-width:200px;"><?= e($task->title) ?></div></td>
                            <td><?= e(($task->assigned_first_name ?? '') . ' ' . ($task->assigned_last_name ?? '')) ?: 'Unassigned' ?></td>
                            <td><?= e($task->department_name ?? '-') ?></td>
                            <td><?= priorityBadge($task->priority ?? '') ?></td>
                            <td><?= statusBadge($task->status ?? '') ?></td>
                            <td class="text-nowrap"><?= formatDate($task->due_date ?? null, 'M j, Y') ?></td>
                            <td class="pe-3 text-end">
                                <a href="<?= rtrim($app['url'], '/') ?>/tasks/view/<?= e($task->id) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No sent tasks found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($tasks) && $totalPages > 1): ?>
    <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == ($_GET['page'] ?? 1)) ? 'active' : '' ?>">
                <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/tasks/sent-tasks?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
