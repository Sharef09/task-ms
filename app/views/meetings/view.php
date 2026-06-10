<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">
        <i class="fas fa-users me-2"></i><?= e($meeting->title) ?>
        <?= statusBadge($meeting->status ?? '') ?>
    </h5>
    <div>
        <?php if (isAdmin() || isManager()): ?>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings/edit/<?= e($meeting->id) ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-8">
                <p class="text-muted small mb-0"><?= nl2br(e($meeting->description ?? '')) ?: '<span class="text-muted">No description</span>' ?></p>
            </div>
            <div class="col-md-4">
                <div class="row g-2 small">
                    <div class="col-5 text-muted">Organizer</div>
                    <div class="col-7 fw-medium"><?= e(($meeting->organizer_first_name ?? '') . ' ' . ($meeting->organizer_last_name ?? '')) ?></div>
                    <div class="col-5 text-muted">Department</div>
                    <div class="col-7"><?= e($meeting->department_name ?? '-') ?></div>
                    <div class="col-5 text-muted">Location</div>
                    <div class="col-7"><?= e($meeting->location ?? '-') ?></div>
                    <div class="col-5 text-muted">Start</div>
                    <div class="col-7"><?= formatDate($meeting->start_date ?? null) ?></div>
                    <div class="col-5 text-muted">End</div>
                    <div class="col-7"><?= formatDate($meeting->end_date ?? null) ?></div>
                </div>
            </div>
        </div>
        <?php if (!empty($meeting->notes)): ?>
        <hr>
        <h6 class="fw-bold small">Notes</h6>
        <p class="small text-muted mb-0"><?= nl2br(e($meeting->notes)) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2"></i>Sessions (<?= count($sessions) ?>)</h6>
                <?php if (isAdmin() || isManager()): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSessionModal"><i class="fas fa-plus"></i></button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($sessions)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($sessions as $s): ?>
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-medium small"><?= e($s->title) ?></div>
                                <div class="text-muted" style="font-size:11px;">
                                    <?= e($s->first_name ?? '') ?> <?= e($s->last_name ?? '') ?>
                                    <?php if ($s->duration_minutes): ?> &middot; <?= e($s->duration_minutes) ?> min<?php endif; ?>
                                </div>
                            </div>
                            <?php if (isAdmin() || isManager()): ?>
                            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/sessions/delete/<?= e($s->id) ?>" class="d-inline" onsubmit="return confirm('Delete this session?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fas fa-times" style="font-size:10px;"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4 small">No sessions added</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-tasks me-2"></i>Action Items (<?= count($tasks) ?>)</h6>
                <?php if (isAdmin() || isManager()): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="fas fa-plus"></i></button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($tasks)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($tasks as $t): ?>
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-medium small"><?= e($t->title) ?></div>
                                <div class="text-muted" style="font-size:11px;">
                                    <?= e($t->first_name ?? '') ?> <?= e($t->last_name ?? '') ?>
                                    <?php if ($t->due_date): ?> &middot; Due: <?= formatDate($t->due_date) ?><?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-<?= $t->status === 'Completed' ? 'success' : ($t->status === 'In Progress' ? 'warning' : 'secondary') ?> bg-opacity-10 text-<?= $t->status === 'Completed' ? 'success' : ($t->status === 'In Progress' ? 'warning' : 'secondary') ?>" style="font-size:10px;"><?= e($t->status) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4 small">No action items</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin() || isManager()): ?>
<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-clock me-1"></i>Add Session</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/sessions/store/<?= e($meeting->id) ?>">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label class="form-label small">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="title" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Presenter</label>
                        <select class="form-select form-select-sm" name="presenter_id">
                            <option value="">Select</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= e($u->id) ?>"><?= e($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Duration (min)</label>
                        <input type="number" class="form-control form-control-sm" name="duration_minutes" min="1">
                    </div>
                    <div>
                        <label class="form-label small">Description</label>
                        <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-tasks me-1"></i>Add Action Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/tasks/store/<?= e($meeting->id) ?>">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label class="form-label small">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="title" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Assigned To</label>
                        <select class="form-select form-select-sm" name="assigned_to">
                            <option value="">Select</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= e($u->id) ?>"><?= e($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Due Date</label>
                        <input type="date" class="form-control form-control-sm" name="due_date">
                    </div>
                    <div>
                        <label class="form-label small">Description</label>
                        <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
