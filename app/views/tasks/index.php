<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0"><i class="fas fa-tasks me-2"></i>Task Management</h5>
    <?php if (!isStaff()): ?>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Create Task
    </a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_nav.php' ?>

<!-- Filter Bar -->
<form method="GET" action="<?= rtrim($app['url'], '/') ?>/tasks" class="row g-2 mb-4">
    <div class="col-md-2">
        <input type="text" class="form-control form-control-sm" name="task_number" placeholder="Task Number" value="<?= e($filters['task_number'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search title..." value="<?= e($filters['search'] ?? '') ?>">
    </div>
    <?php if (!isStaff()): ?>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="assigned_to">
            <option value="">All Users</option>
            <?php foreach ($users as $user): ?>
            <option value="<?= e($user->id) ?>" <?= (!empty($filters['assigned_to']) && $filters['assigned_to'] == $user->id) ? 'selected' : '' ?>><?= e($user->first_name . ' ' . $user->last_name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="department_id">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept->id) ?>" <?= (!empty($filters['department_id']) && $filters['department_id'] == $dept->id) ? 'selected' : '' ?>><?= e($dept->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-1">
        <select class="form-select form-select-sm" name="task_type">
            <option value="">All Types</option>
            <?php foreach ($taskTypes as $type): ?>
            <option value="<?= e($type) ?>" <?= (!empty($filters['task_type']) && $filters['task_type'] === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-1">
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
    <div class="col-md-2">
        <div class="input-group input-group-sm">
            <input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
            <span class="input-group-text bg-white border-start-0 border-end-0"><i class="fas fa-minus text-muted"></i></span>
            <input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
    </div>
    <div class="col-md-12 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
        <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Reset</a>
    </div>
</form>

<!-- Task Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3"><input type="checkbox" id="selectAll"></th>
                        <th>Task Number</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Assigned To</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Progress</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="ps-3"><input type="checkbox" class="task-checkbox" value="<?= e($task->id) ?>"></td>
                            <td class="fw-medium text-nowrap"><?= e($task->task_number) ?></td>
                            <td>
                                <div class="text-truncate fw-medium" style="max-width:180px;"><?= e($task->title) ?></div>
                            </td>
                            <td>
                                <?php
                                $type = $task->task_type ?? 'Normal';
                                if ($type === 'File Attached'): ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning" title="File Attached Task"><i class="fas fa-paperclip me-1"></i>File</span>
                                <?php elseif ($type === 'Initiation'): ?>
                                <span class="badge bg-info bg-opacity-10 text-info" title="Initiation Task"><i class="fas fa-rocket me-1"></i>Init</span>
                                <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" title="Normal Task"><i class="fas fa-tasks me-1"></i>Normal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><?= e(($task->assigned_first_name ?? '') . ' ' . ($task->assigned_last_name ?? '')) ?: '-' ?></td>
                            <td><?= e($task->department_name ?? '-') ?></td>
                            <td><?= priorityBadge($task->priority ?? '') ?></td>
                            <td><?= statusBadge($task->status ?? '') ?></td>
                            <td class="text-nowrap <?= (!empty($task->due_date) && strtotime($task->due_date) < time() && $task->status !== 'Completed') ? 'text-danger fw-medium' : '' ?>"><?= formatDate($task->due_date ?? null, 'M j, Y') ?></td>
                            <td style="min-width:100px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-<?= ($task->progress ?? 0) >= 100 ? 'success' : (($task->progress ?? 0) >= 50 ? 'warning' : 'primary') ?>" role="progressbar" style="width:<?= e($task->progress ?? 0) ?>%" aria-valuenow="<?= e($task->progress ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small text-muted" style="min-width:28px;"><?= e($task->progress ?? 0) ?>%</span>
                                </div>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/tasks/view/<?= e($task->id) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                    <?php if (!isStaff()): ?>
                                    <a href="<?= rtrim($app['url'], '/') ?>/tasks/edit/<?= e($task->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-info" title="Assign" data-assign-task="<?= e($task->id) ?>" data-number="<?= e($task->task_number) ?>"><i class="fas fa-user-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Reassign" data-reassign-task="<?= e($task->id) ?>" data-number="<?= e($task->task_number) ?>"><i class="fas fa-user-tag"></i></button>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/complete/<?= e($task->id) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Complete" onclick="return confirm('Mark task <?= e($task->task_number) ?> as completed?')" <?= $task->status === 'Completed' ? 'disabled' : '' ?>><i class="fas fa-check"></i></button>
                                    </form>
                                    <?php if (isAdmin()): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-delete-task="<?= e($task->id) ?>" data-number="<?= e($task->task_number) ?>"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                                No tasks found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($tasks)): ?>
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" style="width:auto;" id="bulkAction">
                <option value="">Bulk Actions</option>
                <option value="assign">Assign</option>
                <option value="complete">Complete</option>
                <option value="delete">Delete</option>
                <option value="export">Export</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="applyBulkAction" disabled>Apply</button>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/tasks?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '' ?><?= !empty($filters['priority']) ? '&priority=' . urlencode($filters['priority']) : '' ?><?= !empty($filters['assigned_to']) ? '&assigned_to=' . urlencode($filters['assigned_to']) : '' ?><?= !empty($filters['department_id']) ? '&department_id=' . urlencode($filters['department_id']) : '' ?><?= !empty($filters['task_number']) ? '&task_number=' . urlencode($filters['task_number']) : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . urlencode($filters['date_from']) : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . urlencode($filters['date_to']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="deleteTaskForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<form method="POST" id="bulkActionForm" action="<?= rtrim($app['url'], '/') ?>/tasks/bulk-update">
    <?= csrf_field() ?>
    <input type="hidden" name="task_ids" id="bulkTaskIds">
    <input type="hidden" name="action" id="bulkActionType">
    <input type="hidden" name="assignee_id" id="bulkAssigneeId">
</form>

<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small text-danger fw-medium"><i class="fas fa-exclamation-circle me-1"></i>This task will be permanently deleted. Are you sure you want to delete task <strong id="deleteTaskNumber"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteTaskBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<?php if (!isStaff()): ?>
<div class="modal fade" id="reassignTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-tag text-warning me-1"></i>Reassign Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="small mb-2">Reassign task <strong id="reassignTaskNumber"></strong> to:</p>
                <select class="form-select form-select-sm" id="reassignUserId">
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= e($user->id) ?>"><?= e($user->first_name . ' ' . $user->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning" id="confirmReassignBtn"><i class="fas fa-user-tag me-1"></i>Reassign</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.task-checkbox');
    const applyBtn = document.getElementById('applyBulkAction');
    const bulkAction = document.getElementById('bulkAction');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) { cb.checked = this.checked; }.bind(this));
            toggleBulkBtn();
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', toggleBulkBtn);
    });

    function toggleBulkBtn() {
        const checked = document.querySelectorAll('.task-checkbox:checked');
        applyBtn.disabled = checked.length === 0 || bulkAction.value === '';
    }

    if (bulkAction) {
        bulkAction.addEventListener('change', toggleBulkBtn);
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.task-checkbox:checked:not(#selectAll)');
            if (checked.length === 0) return;
            const ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
            const action = bulkAction.value;
            if (action === 'export') {
                window.location.href = '<?= rtrim($app['url'], '/') ?>/tasks/export?ids=' + ids;
                return;
            }
            document.getElementById('bulkTaskIds').value = ids;
            document.getElementById('bulkActionType').value = action;
            if (action === 'assign') {
                const assignee = prompt('Enter user ID to assign tasks to:');
                if (!assignee) return;
                document.getElementById('bulkAssigneeId').value = assignee;
            }
            document.getElementById('bulkActionForm').submit();
        });
    }

    // Delete task
    let deleteTaskId = null;
    document.querySelectorAll('[data-delete-task]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteTaskId = this.getAttribute('data-delete-task');
            document.getElementById('deleteTaskNumber').textContent = this.getAttribute('data-number');
            new bootstrap.Modal(document.getElementById('deleteTaskModal')).show();
        });
    });

    document.getElementById('confirmDeleteTaskBtn').addEventListener('click', function() {
        if (deleteTaskId) {
            const form = document.getElementById('deleteTaskForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/tasks/delete/' + deleteTaskId;
            form.submit();
        }
    });

    // Assign / Reassign task (shared modal)
    let activeTaskId = null;
    let activeTaskAction = '';

    document.querySelectorAll('[data-assign-task]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeTaskId = this.getAttribute('data-assign-task');
            activeTaskAction = 'assign';
            document.getElementById('reassignTaskNumber').textContent = this.getAttribute('data-number');
            document.getElementById('reassignUserId').value = '';
            new bootstrap.Modal(document.getElementById('reassignTaskModal')).show();
        });
    });

    document.querySelectorAll('[data-reassign-task]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeTaskId = this.getAttribute('data-reassign-task');
            activeTaskAction = 'reassign';
            document.getElementById('reassignTaskNumber').textContent = this.getAttribute('data-number');
            document.getElementById('reassignUserId').value = '';
            new bootstrap.Modal(document.getElementById('reassignTaskModal')).show();
        });
    });

    document.getElementById('confirmReassignBtn').addEventListener('click', function() {
        const userId = document.getElementById('reassignUserId').value;
        if (activeTaskId && userId) {
            const form = document.getElementById('deleteTaskForm');
            const route = activeTaskAction === 'assign' ? 'assign' : 'reassign';
            form.action = '<?= rtrim($app['url'], '/') ?>/tasks/' + route + '/' + activeTaskId;
            form.querySelectorAll('input[name="assigned_to"]').forEach(function(el) { el.remove(); });
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'assigned_to';
            input.value = userId;
            form.appendChild(input);
            form.submit();
        }
    });
    document.getElementById('reassignTaskModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('reassignTaskNumber').textContent = '';
        document.getElementById('reassignUserId').value = '';
    });
});
</script>
