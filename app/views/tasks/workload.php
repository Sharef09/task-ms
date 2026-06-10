<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2"></i>Task Workload</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Tasks</a>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Workload by User</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Total Tasks</th>
                                <th>In Progress</th>
                                <th>Completed</th>
                                <th>Overdue</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($workload)): ?>
                                <?php foreach ($workload as $w): ?>
                                <tr>
                                    <td class="ps-3 fw-medium"><?= e($w->first_name . ' ' . $w->last_name) ?></td>
                                    <td><?= (int)($w->total_tasks ?? 0) ?></td>
                                    <td><span class="badge bg-info"><?= (int)($w->in_progress ?? 0) ?></span></td>
                                    <td><span class="badge bg-success"><?= (int)($w->completed ?? 0) ?></span></td>
                                    <td><span class="badge bg-danger"><?= (int)($w->overdue ?? 0) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= (int)($w->pending ?? 0) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">No workload data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-building me-2"></i>Department Performance</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Department</th>
                                <th>Completed</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deptPerformance)): ?>
                                <?php foreach ($deptPerformance as $d): ?>
                                <tr>
                                    <td class="ps-3"><?= e($d->department_name ?? $d->name ?? '-') ?></td>
                                    <td><span class="badge bg-success"><?= (int)($d->completed ?? 0) ?></span></td>
                                    <td><?= (int)($d->total ?? 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-5">No department data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
