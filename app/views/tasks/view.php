<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$currentUser = $_SESSION['user'] ?? null;
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fas fa-tasks me-2"></i><?= e($task->task_number) ?>
            <?= statusBadge($task->status ?? '') ?>
        </h5>
    </div>
    <div class="d-flex gap-1 flex-wrap">
        <?php if (!isStaff()): ?>
        <a href="<?= rtrim($app['url'], '/') ?>/tasks/edit/<?= e($task->id) ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="fas fa-user-check me-1"></i>Assign</button>
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reassignModal"><i class="fas fa-user-tag me-1"></i>Reassign</button>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/archive/<?= e($task->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-dark"><i class="fas fa-archive me-1"></i>Archive</button>
        </form>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/clone/<?= e($task->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-info"><i class="fas fa-copy me-1"></i>Clone</button>
        </form>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fas fa-trash me-1"></i>Delete</button>
        <?php endif; ?>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/in-progress/<?= e($task->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-warning" <?= in_array($task->status, ['Completed', 'Cancelled', 'In Progress']) ? 'disabled' : '' ?>><i class="fas fa-play me-1"></i>In Progress</button>
        </form>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/complete/<?= e($task->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-success" <?= $task->status === 'Completed' ? 'disabled' : '' ?>><i class="fas fa-check me-1"></i>Complete</button>
        </form>
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/reopen/<?= e($task->id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $task->status !== 'Completed' ? 'disabled' : '' ?>><i class="fas fa-redo me-1"></i>Reopen</button>
        </form>
        <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php include __DIR__ . '/_nav.php' ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-8">
                <h6 class="fw-bold mb-2"><?= e($task->title) ?></h6>
                <p class="text-muted small mb-0"><?= nl2br(e($task->description ?? '')) ?: '<span class="text-muted">No description provided.</span>' ?></p>
            </div>
            <div class="col-md-4">
                <div class="row g-2 small">
                    <div class="col-6"><span class="text-muted">Category</span></div>
                    <div class="col-6 text-end fw-medium"><?= e($task->category_name ?? '-') ?></div>
                    <div class="col-6"><span class="text-muted">Department</span></div>
                    <div class="col-6 text-end fw-medium"><?= e($task->department_name ?? '-') ?></div>
                    <div class="col-6"><span class="text-muted">Priority</span></div>
                    <div class="col-6 text-end"><?= priorityBadge($task->priority ?? '') ?></div>
                    <div class="col-6"><span class="text-muted">Assigned To</span></div>
                    <div class="col-6 text-end fw-medium"><?= e(($task->assigned_first_name ?? '') . ' ' . ($task->assigned_last_name ?? '')) ?: '-' ?></div>
                    <div class="col-6"><span class="text-muted">Assigned By</span></div>
                    <div class="col-6 text-end fw-medium"><?= e(($task->assigned_by_first_name ?? '') . ' ' . ($task->assigned_by_last_name ?? '')) ?: '-' ?></div>
                    <div class="col-6"><span class="text-muted">Created By</span></div>
                    <div class="col-6 text-end fw-medium"><?= e(($task->created_first_name ?? '') . ' ' . ($task->created_last_name ?? '')) ?: '-' ?></div>
                    <div class="col-6"><span class="text-muted">Start Date</span></div>
                    <div class="col-6 text-end"><?= formatDate($task->start_date ?? null) ?></div>
                    <div class="col-6"><span class="text-muted">Due Date</span></div>
                    <div class="col-6 text-end <?= (!empty($task->due_date) && strtotime($task->due_date) < time() && $task->status !== 'Completed') ? 'text-danger fw-medium' : '' ?>"><?= formatDate($task->due_date ?? null) ?></div>
                    <div class="col-6"><span class="text-muted">Estimated Hours</span></div>
                    <div class="col-6 text-end"><?= e($task->estimated_hours ?? '-') ?></div>
                    <div class="col-6"><span class="text-muted">Actual Hours</span></div>
                    <div class="col-6 text-end"><?= e($task->actual_hours ?? '-') ?></div>
                    <div class="col-6"><span class="text-muted">Status</span></div>
                    <div class="col-6 text-end"><?= statusBadge($task->status ?? '') ?></div>
                    <div class="col-6"><span class="text-muted">Task Type</span></div>
                    <div class="col-6 text-end">
                        <?php $ttype = $task->task_type ?? 'Normal';
                        if ($ttype === 'File Attached'): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning"><i class="fas fa-paperclip me-1"></i>File Attached</span>
                        <?php elseif ($ttype === 'Initiation'): ?>
                        <span class="badge bg-info bg-opacity-10 text-info"><i class="fas fa-rocket me-1"></i>Initiation</span>
                        <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-tasks me-1"></i>Normal</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 mt-2">
                        <span class="text-muted">Progress</span>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <div class="progress flex-grow-1" style="height:8px;">
                                <div class="progress-bar bg-<?= ($task->progress ?? 0) >= 100 ? 'success' : (($task->progress ?? 0) >= 50 ? 'warning' : 'primary') ?>" role="progressbar" style="width:<?= e($task->progress ?? 0) ?>%" aria-valuenow="<?= e($task->progress ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="small fw-medium"><?= e($task->progress ?? 0) ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-comments me-2"></i>Comments (<?= count($comments ?? []) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="border-bottom px-3 py-3">
                        <div class="d-flex gap-2">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                                    <i class="fas fa-user text-muted small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-medium small"><?= e($comment->first_name . ' ' . $comment->last_name ?? 'Unknown') ?></span>
                                    <span class="text-muted" style="font-size:11px;"><?= timeAgo($comment->created_at ?? '') ?></span>
                                    <?php if (!empty($comment->is_internal)): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size:10px;"><i class="fas fa-lock me-1"></i>Internal Note</span>
                                    <?php endif; ?>
                                </div>
                                <p class="small mb-1"><?= nl2br(e($comment->comment ?? '')) ?></p>
                                <?php if (!empty($comment->attachment)): ?>
                                <a href="<?= rtrim($app['url'], '/') ?>/<?= e($comment->attachment) ?>" class="small text-decoration-none" target="_blank"><i class="fas fa-paperclip me-1"></i>View Attachment</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center text-muted py-4 small">
                    <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                    No comments yet
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2"></i>Add Comment</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/comments/store" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="task_id" value="<?= e($task->id) ?>">
                    <div class="mb-2">
                        <textarea class="form-control" name="content" rows="3" placeholder="Write a comment..." required></textarea>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="file" class="form-control form-control-sm" name="attachment" style="max-width:200px;">
                        <?php if ($currentUser && $currentUser->role_slug === 'administrator'): ?>
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="is_internal" name="is_internal" value="1">
                            <label class="form-check-label small" for="is_internal"><i class="fas fa-lock me-1"></i>Internal Note</label>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-sm ms-auto"><i class="fas fa-paper-plane me-1"></i>Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-paperclip me-2"></i>Attachments (<?= count($attachments ?? []) ?>)</h6>
                <?php $ttype = $task->task_type ?? 'Normal';
                if ($ttype === 'Normal'): ?>
                <span class="badge bg-light text-muted small"><i class="fas fa-info-circle me-1"></i>Normal task</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($attachments)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($attachments as $file): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 px-3 py-2">
                            <i class="fas fa-file text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate fw-medium"><?= e($file->original_name ?? '') ?></div>
                                <div class="text-muted" style="font-size:10px;"><?= e($file->first_name ?? '') ?> <?= e($file->last_name ?? '') ?> &middot; <?= formatDate($file->created_at ?? null) ?></div>
                            </div>
                            <?php
                            $downloadUrl = '#';
                            if (($file->source ?? 'task_attachment') === 'user_file') {
                                $downloadUrl = rtrim($app['url'], '/') . '/user-files/download/' . e($file->id);
                            } elseif ($file->id > 0) {
                                $downloadUrl = rtrim($app['url'], '/') . '/tasks/attachments/download/' . e($file->id);
                            } else {
                                $downloadUrl = rtrim($app['url'], '/') . '/' . e($file->file_path);
                            }
                            ?>
                            <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-outline-primary" download><i class="fas fa-download"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                <div class="text-center text-muted py-4 small">
                    <i class="fas fa-paperclip fa-2x mb-2 d-block"></i>
                    No attachments
                </div>
                <?php endif; ?>
            </div>
            <?php if (($task->task_type ?? 'Normal') !== 'Normal'): ?>
            <div class="card-footer bg-white border-top p-3">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/attachments/upload" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="task_id" value="<?= e($task->id) ?>">
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control" name="file" required>
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-upload me-1"></i>Upload</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Task History</h6>
            </div>
            <div class="card-body p-0" style="max-height:400px; overflow-y:auto;">
                <?php if (!empty($history)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($history as $entry):
                            $oldVal = $entry->old_value ?? '';
                            $newVal = $entry->new_value ?? '';
                            $field = $entry->field_changed ?? $entry->field ?? '';
                            $decodedOld = json_decode($oldVal, true);
                            $decodedNew = json_decode($newVal, true);
                        ?>
                        <li class="list-group-item px-3 py-2 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:24px; height:24px;">
                                        <i class="fas fa-exchange-alt text-muted" style="font-size:10px;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small">
                                        <span class="fw-medium"><?= e(($entry->first_name ?? '') . ' ' . ($entry->last_name ?? 'System')) ?></span>
                                        <span class="text-muted"><?= $field === 'created' ? 'created this task' : 'changed' ?></span>
                                        <?php if ($field !== 'created'): ?>
                                        <span class="fw-medium"><?= e(ucfirst(str_replace('_', ' ', $field))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php if ($field === 'created' && is_array($decodedNew)): ?>
                                            <span class="text-success">Title: <?= e($decodedNew['title'] ?? '') ?></span>
                                            <span class="text-muted mx-1">&middot;</span>
                                            <span>Priority: <?= e($decodedNew['priority'] ?? '') ?></span>
                                            <?php if (!empty($decodedNew['assigned_to'])): ?>
                                            <span class="text-muted mx-1">&middot;</span>
                                            <span>Assigned to: User #<?= e($decodedNew['assigned_to']) ?></span>
                                            <?php endif; ?>
                                        <?php elseif ($field === 'updated' && is_array($decodedNew)): ?>
                                            <span class="text-success">Updated: <?= e($decodedNew['title'] ?? '') ?></span>
                                        <?php elseif ($field === 'assigned'): ?>
                                            <span class="text-success">Assigned to User #<?= e($newVal) ?></span>
                                        <?php elseif (!empty($oldVal) || !empty($newVal)): ?>
                                            <?php if (is_array($decodedOld) || is_array($decodedNew)): ?>
                                                <span class="text-muted">Details updated</span>
                                            <?php else: ?>
                                                <span class="text-danger"><del><?= e($oldVal) ?></del></span>
                                                <i class="fas fa-arrow-right mx-1" style="font-size:10px;"></i>
                                                <span class="text-success"><?= e($newVal) ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size:10px;"><?= timeAgo($entry->created_at ?? '') ?></div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                <div class="text-center text-muted py-4 small">
                    <i class="fas fa-history fa-2x mb-2 d-block"></i>
                    No history recorded
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!isStaff()): ?>
<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-check text-info me-1"></i>Assign Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/assign/<?= e($task->id) ?>">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <p class="small mb-2">Assign <strong><?= e($task->task_number) ?></strong> to:</p>
                    <select class="form-select form-select-sm" name="assigned_to" required>
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= e($user->id) ?>"><?= e($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-info text-white"><i class="fas fa-user-check me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete task <strong><?= e($task->task_number) ?></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/delete/<?= e($task->id) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reassign Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-tag text-warning me-1"></i>Reassign Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/reassign/<?= e($task->id) ?>">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <p class="small mb-2">Reassign <strong><?= e($task->task_number) ?></strong> to:</p>
                    <select class="form-select form-select-sm" name="user_id" required>
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= e($user->id) ?>"><?= e($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-user-tag me-1"></i>Reassign</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
