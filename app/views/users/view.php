<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$fullName = e($user->first_name . ' ' . $user->last_name);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-user-circle me-2"></i>User Details</h5>
    <div class="d-flex gap-1">
        <a href="<?= rtrim($app['url'], '/') ?>/users/edit/<?= e($user->id) ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="<?= rtrim($app['url'], '/') ?>/users" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width:96px; height:96px;">
                    <i class="fas fa-user fa-3x text-muted"></i>
                </div>
                <h5 class="fw-bold mb-1"><?= $fullName ?></h5>
                <div class="text-muted small mb-2"><?= e($user->role_name ?? 'No Role') ?></div>
                <div class="mb-3"><?= statusBadge(ucfirst($user->status)) ?></div>
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Employee ID</span><span class="fw-medium"><?= e($user->employee_id ?? '-') ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Username</span><span><?= e($user->username) ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Email</span><span><?= e($user->email) ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Mobile</span><span><?= e($user->mobile ?? '-') ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Department</span><span><?= e($user->department_name ?? '-') ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Created At</span><span><?= formatDate($user->created_at) ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="h4 fw-bold mb-0 text-primary"><?= e($taskStats->total_tasks ?? 0) ?></div>
                    <div class="small text-muted">Total Assigned</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="h4 fw-bold mb-0 text-success"><?= e($taskStats->completed_tasks ?? 0) ?></div>
                    <div class="small text-muted">Completed</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="h4 fw-bold mb-0 text-warning"><?= e($taskStats->in_progress_tasks ?? 0) ?></div>
                    <div class="small text-muted">In Progress</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="h4 fw-bold mb-0 text-danger"><?= e($taskStats->overdue ?? 0) ?></div>
                    <div class="small text-muted">Overdue</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-history text-secondary me-2"></i>Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                        <li class="list-group-item px-3 py-2 d-flex align-items-start gap-2">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                                    <i class="fas fa-circle text-<?= e($activity->action === 'created' ? 'success' : ($activity->action === 'updated' ? 'info' : ($activity->action === 'deleted' ? 'danger' : 'secondary'))) ?> fa-xs"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate">
                                    <span class="fw-medium"><?= e(ucfirst($activity->action)) ?></span>
                                    <span class="text-muted"><?= e($activity->module) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:11px;"><?= timeAgo($activity->created_at ?? '') ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">No recent activity</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
