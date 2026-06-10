<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/components/dashboard-card.php';
require_once dirname(__DIR__, 3) . '/components/chart-widget.php';

$statusColors = ['#dc3545', '#0d6efd', '#ffc107', '#198754', '#6c757d', '#212529', '#fd7e14', '#20c997'];
$priorityColors = ['#198754', '#0d6efd', '#ffc107', '#dc3545'];
$chartColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#d63384'];
$blueGradient = ['rgba(13,110,253,0.7)', 'rgba(13,110,253,0.3)', 'rgba(13,110,253,0.1)'];

$hasStatusData = !empty($taskStatusData) && array_sum(array_column($taskStatusData, 'value')) > 0;
$hasPriorityData = !empty($taskPriorityData) && array_sum(array_column($taskPriorityData, 'value')) > 0;
$hasTrendData = !empty($monthlyTrendData) && array_sum(array_column($monthlyTrendData, 'value')) > 0;
$hasPerformerData = !empty($topPerformers) && array_sum(array_map(fn($p) => (int)($p->completed_tasks ?? 0), $topPerformers)) > 0;
$hasDeptData = !empty($departmentData) && array_sum(array_column($departmentData, 'value')) > 0;
?>

<!-- Stats Row 1 -->
<div class="row g-3 mb-4">
    <?php if (!isStaff()): ?>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-users', 'Total Users', e($stats->total_users ?? 0), null, true, 'primary') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-user-check', 'Active Users', e($stats->active_users ?? 0), null, true, 'success') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-user-slash', 'Inactive Users', e($stats->inactive_users ?? 0), null, true, 'secondary') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-tasks', 'My Tasks', e($stats->total_tasks ?? 0), null, true, 'info') ?>
    </div>
    <?php else: ?>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-tasks', 'My Tasks', e($stats->total_tasks ?? 0), null, true, 'info') ?>
    </div>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-door-open', 'Open Tasks', e($stats->open_tasks ?? 0), null, true, 'primary') ?>
    </div>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-user-tag', 'Assigned', e($stats->assigned ?? 0), null, true, 'info') ?>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Row 2 -->
<div class="row g-3 mb-4">
    <?php if (!isStaff()): ?>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-door-open', 'Open Tasks', e($stats->open_tasks ?? 0), null, true, 'primary') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-user-tag', 'Assigned', e($stats->assigned ?? 0), null, true, 'info') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-spinner', 'In Progress', e($stats->in_progress ?? 0), null, true, 'warning') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-check-circle', 'Completed', e($stats->completed ?? 0), null, true, 'success') ?>
    </div>
    <?php else: ?>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-spinner', 'In Progress', e($stats->in_progress ?? 0), null, true, 'warning') ?>
    </div>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-check-circle', 'Completed', e($stats->completed ?? 0), null, true, 'success') ?>
    </div>
    <div class="col-sm-6 col-md-4">
        <?= dashboardCard('fa-exclamation-triangle', 'Overdue', e($stats->overdue ?? 0), null, true, 'danger') ?>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Row 3 (admin only) -->
<?php if (!isStaff()): ?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-exclamation-triangle', 'Overdue', e($stats->overdue ?? 0), null, true, 'danger') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-calendar-day', 'Today\'s Tasks', e($stats->today_tasks ?? 0), null, true, 'primary') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-calendar-week', 'This Week', e($stats->this_week_tasks ?? 0), null, true, 'info') ?>
    </div>
    <div class="col-sm-6 col-xl-3">
        <?= dashboardCard('fa-calendar-alt', 'This Month', e($stats->this_month_tasks ?? 0), null, true, 'success') ?>
    </div>
</div>
<?php endif; ?>

<!-- Chart Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-md-<?= isStaff() ? '6' : '4' ?>">
        <?php if ($hasStatusData):
            $statusLabels = array_column($taskStatusData, 'label');
            $statusValues = array_column($taskStatusData, 'value');
            echo chartWidget('taskStatusChart', 'doughnut',
                $statusLabels,
                [['data' => $statusValues, 'backgroundColor' => array_slice($statusColors, 0, count($statusValues)), 'borderWidth' => 0]],
                ['plugins' => ['legend' => ['position' => 'right']], 'cutout' => '65%'],
                'Task Status Distribution'
            );
        else: ?>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Task Status Distribution</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center text-muted small py-5">No data available</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-<?= isStaff() ? '6' : '4' ?>">
        <?php if ($hasPriorityData):
            $priorityLabels = array_column($taskPriorityData, 'label');
            $priorityValues = array_column($taskPriorityData, 'value');
            echo chartWidget('taskPriorityChart', 'bar',
                $priorityLabels,
                [['label' => 'Tasks', 'data' => $priorityValues, 'backgroundColor' => array_slice($priorityColors, 0, count($priorityValues)), 'borderRadius' => 6, 'borderSkipped' => false]],
                ['plugins' => ['legend' => ['display' => false]]],
                'Task Priority Distribution'
            );
        else: ?>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Task Priority Distribution</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center text-muted small py-5">No data available</div>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!isStaff()): ?>
    <div class="col-md-4">
        <?php if ($hasTrendData):
            $trendLabels = array_column($monthlyTrendData, 'label');
            $trendValues = array_column($monthlyTrendData, 'value');
            echo chartWidget('monthlyTrendChart', 'line',
                $trendLabels,
                [['label' => 'Completed', 'data' => $trendValues, 'borderColor' => '#0d6efd', 'backgroundColor' => 'rgba(13,110,253,0.08)', 'fill' => true, 'tension' => 0.4, 'pointRadius' => 4, 'pointHoverRadius' => 6, 'borderWidth' => 2]],
                ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['ticks' => ['stepSize' => 1]]]],
                'Monthly Task Completion Trend'
            );
        else: ?>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Monthly Task Completion Trend</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center text-muted small py-5">No data available</div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Chart Row 2 (admin only) -->
<?php if (!isStaff()): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <?php if ($hasPerformerData):
            $performerNames = array_map(fn($p) => ($p->first_name ?? '') . ' ' . ($p->last_name ?? ''), $topPerformers);
            $performerRates = array_map(fn($p) => (int)($p->completed_tasks ?? 0), $topPerformers);
            echo chartWidget('topPerformersChart', 'bar',
                $performerNames,
                [['label' => 'Completed', 'data' => $performerRates, 'backgroundColor' => array_slice($chartColors, 0, count($performerRates)), 'borderRadius' => 6, 'borderSkipped' => false]],
                ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['ticks' => ['stepSize' => 1]]]],
                'Top Performers (Last 30 Days)'
            );
        else: ?>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Top Performers</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center text-muted small py-5">No data available</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <?php if ($hasDeptData):
            $deptNames = array_column($departmentData, 'label');
            $deptCompleted = array_column($departmentData, 'value');
            echo chartWidget('deptPerformanceChart', 'bar',
                $deptNames,
                [['label' => 'Completed', 'data' => $deptCompleted, 'backgroundColor' => $blueGradient[0], 'borderRadius' => 6, 'borderSkipped' => false]],
                ['plugins' => ['legend' => ['display' => false]]],
                'Department Performance'
            );
        else: ?>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Department Performance</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center text-muted small py-5">No data available</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Widgets Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-tasks text-primary me-2"></i>Recent Tasks</h6>
                <a href="<?= rtrim($app['url'], '/') ?>/tasks" class="small text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Task</th>
                                <th>Status</th>
                                <th class="pe-3">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentTasks)): ?>
                                <?php foreach ($recentTasks as $task): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-medium text-truncate" style="max-width:160px;"><?= e($task->title ?? '-') ?></div>
                                    </td>
                                    <td><?= statusBadge($task->status ?? '') ?></td>
                                    <td class="pe-3 text-nowrap"><?= formatDate($task->due_date ?? null) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No recent tasks</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-bell text-warning me-2"></i>Notifications</h6>
                <a href="<?= rtrim($app['url'], '/') ?>/notifications" class="small text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                        <li class="list-group-item border-bottom d-flex align-items-start gap-2 px-3 py-3">
                            <div class="flex-shrink-0">
                                <span class="badge bg-<?= e($notif->type === 'success' ? 'success' : ($notif->type === 'warning' ? 'warning' : ($notif->type === 'danger' ? 'danger' : 'primary'))) ?> rounded-circle p-2" style="width:8px; height:8px; display:inline-block;"></span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate"><?= e($notif->message ?? '') ?></div>
                                <div class="text-muted" style="font-size:11px;"><?= timeAgo($notif->created_at ?? '') ?></div>
                            </div>
                            <?php if (!($notif->is_read ?? false)): ?>
                            <span class="badge bg-primary rounded-pill" style="width:6px; height:6px; padding:0;"></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">No notifications</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-calendar-alt text-danger me-2"></i>Upcoming Deadlines</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($upcomingDeadlines)): ?>
                        <?php foreach ($upcomingDeadlines as $task): ?>
                        <li class="list-group-item border-bottom px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="min-w-0 me-2">
                                    <div class="small fw-medium text-truncate"><?= e($task->title ?? 'Untitled') ?></div>
                                    <div class="text-muted" style="font-size:11px;">
                                        <i class="fas fa-user me-1"></i><?= e(($task->assigned_first_name ?? '') . ' ' . ($task->assigned_last_name ?? '')) ?>
                                    </div>
                                </div>
                                <span class="small text-nowrap <?= (strtotime($task->due_date ?? '') < time()) ? 'text-danger fw-medium' : 'text-muted' ?>">
                                    <i class="far fa-clock me-1"></i><?= formatDate($task->due_date ?? null, 'M j') ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">No upcoming deadlines</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Widgets Row 2 -->
<div class="row g-3">
    <?php if (!isStaff()): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-sign-in-alt text-info me-2"></i>Recent Logins</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recentLogins)): ?>
                        <?php foreach ($recentLogins as $login): ?>
                        <li class="list-group-item border-bottom px-3 py-2 d-flex align-items-center gap-2">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                    <i class="fas fa-user text-muted small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate"><?= e(($login->first_name ?? '') . ' ' . ($login->last_name ?? '') ?: 'Unknown') ?></div>
                                <div class="text-muted" style="font-size:11px;"><?= timeAgo($login->created_at ?? '') ?></div>
                            </div>
                            <span class="small text-muted"><?= e($login->ip_address ?? '') ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">No recent logins</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-history text-secondary me-2"></i>Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                        <li class="list-group-item border-bottom px-3 py-2 d-flex align-items-start gap-2">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                                    <i class="fas fa-circle text-<?= e($activity->action === 'created' ? 'success' : ($activity->action === 'updated' ? 'info' : ($activity->action === 'deleted' ? 'danger' : 'secondary'))) ?> fa-xs"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate">
                                    <span class="fw-medium"><?= e($activity->first_name ?? 'System') ?></span>
                                    <?= e($activity->action ?? '') ?>
                                    <?= e($activity->module ?? '') ?>
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
    <?php endif; ?>
    <div class="col-lg-<?= isStaff() ? '12' : '4' ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-exclamation-circle text-danger me-2"></i>Overdue Tasks</h6>
                <span class="badge bg-danger rounded-pill"><?= e(count($overdueTasks ?? [])) ?></span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($overdueTasks)): ?>
                        <?php foreach ($overdueTasks as $task): ?>
                        <li class="list-group-item border-bottom px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="min-w-0 me-2">
                                    <div class="small fw-medium text-truncate"><?= e($task->title ?? 'Untitled') ?></div>
                                    <div class="text-muted" style="font-size:11px;">
                                        <i class="fas fa-user me-1"></i><?= e(($task->assigned_first_name ?? '') . ' ' . ($task->assigned_last_name ?? '')) ?>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="small text-danger fw-medium"><?= formatDate($task->due_date ?? null, 'M j') ?></div>
                                    <div class="text-muted" style="font-size:10px;">Overdue</div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fas fa-check-circle text-success me-1"></i>No overdue tasks
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
