<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$reportTypes = ['tasks', 'users', 'performance', 'departments', 'activity', 'login', 'audit', 'notifications'];
$activeType = $reportType ?? 'tasks';
$reportCount = is_array($reportData ?? null) ? count($reportData) : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Reports</h5>
        <p class="text-muted small mb-0 mt-1">Generate and export system reports</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item small" href="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>/export/xlsx?<?= http_build_query($filters ?? []) ?>"><i class="fas fa-file-excel text-success me-2"></i>Excel</a></li>
                <li><a class="dropdown-item small" href="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>/export/csv?<?= http_build_query($filters ?? []) ?>"><i class="fas fa-file-csv text-info me-2"></i>CSV</a></li>
                <li><a class="dropdown-item small" href="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>/export/pdf?<?= http_build_query($filters ?? []) ?>"><i class="fas fa-file-pdf text-danger me-2"></i>PDF</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#emailExportModal"><i class="fas fa-envelope text-secondary me-2"></i>Email</a></li>
                <li><a class="dropdown-item small" href="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>/export/print?<?= http_build_query($filters ?? []) ?>"><i class="fas fa-print text-dark me-2"></i>Print</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <ul class="nav nav-tabs border-0" id="reportTabs" role="tablist" style="padding:0 16px;">
            <?php foreach ($reportTypes as $type): ?>
            <?php
            $icon = match($type) {
                'tasks' => 'fa-tasks',
                'users' => 'fa-users',
                'performance' => 'fa-chart-line',
                'departments' => 'fa-building',
                'activity' => 'fa-history',
                'login' => 'fa-sign-in-alt',
                'audit' => 'fa-shield-alt',
                'notifications' => 'fa-bell',
                default => 'fa-file',
            };
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link border-0 rounded-0 px-3 py-3 fw-medium small <?= $type === $activeType ? 'active' : '' ?>" href="<?= rtrim($app['url'], '/') ?>/reports/<?= $type ?>">
                    <i class="fas <?= $icon ?> me-1"></i><?= ucfirst($type) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Filter Panel -->
<form method="GET" action="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>" class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 small"><i class="fas fa-sliders-h me-1 text-primary"></i>Filter Report</h6>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">Date From</label>
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">Date To</label>
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">Department</label>
                <select class="form-select form-select-sm" name="department_id">
                    <option value="">All</option>
                    <?php foreach ($departments ?? [] as $dept): ?>
                    <option value="<?= e($dept->id ?? $dept['id'] ?? '') ?>" <?= (!empty($filters['department_id']) && ($filters['department_id'] == ($dept->id ?? $dept['id'] ?? ''))) ? 'selected' : '' ?>><?= e($dept->name ?? $dept['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">User</label>
                <select class="form-select form-select-sm" name="user_id">
                    <option value="">All</option>
                    <?php foreach ($users ?? [] as $user): ?>
                    <option value="<?= e($user->id ?? $user['id'] ?? '') ?>" <?= (!empty($filters['user_id']) && ($filters['user_id'] == ($user->id ?? $user['id'] ?? ''))) ? 'selected' : '' ?>><?= e(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">All</option>
                    <?php foreach ($statuses ?? [] as $status): ?>
                    <option value="<?= e($status) ?>" <?= (!empty($filters['status']) && $filters['status'] === $status) ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">Priority</label>
                <select class="form-select form-select-sm" name="priority">
                    <option value="">All</option>
                    <?php foreach ($priorities ?? [] as $priority): ?>
                    <option value="<?= e($priority) ?>" <?= (!empty($filters['priority']) && $filters['priority'] === $priority) ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-sync me-1"></i>Generate</button>
            <a href="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo me-1"></i>Reset</a>
        </div>
    </div>
</form>

<!-- Summary Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2 rounded-pill">
            <i class="fas fa-database me-1"></i><?= e($reportCount) ?> Records
        </span>
        <?php $label = match($activeType) {
            'tasks' => 'Task',
            'users' => 'User',
            'performance' => 'Performance',
            'departments' => 'Department',
            'activity' => 'Activity',
            'login' => 'Login',
            'audit' => 'Audit',
            'notifications' => 'Notification',
            default => 'Report',
        }; ?>
        <span class="text-muted small"><i class="fas fa-tag me-1"></i><?= e($label) ?> Report</span>
        <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
        <span class="text-muted small"><i class="fas fa-calendar me-1"></i><?= e($filters['date_from'] ?? '∞') ?> — <?= e($filters['date_to'] ?? '∞') ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<?php if (!empty($chartData)): ?>
<div class="row g-3 mb-4">
    <?php foreach ($chartData as $i => $chart): ?>
    <?php if (!empty($chart['data']) && array_sum($chart['data']) > 0): ?>
    <div class="col-md-<?= count($chartData) >= 3 ? 4 : (count($chartData) === 2 ? 6 : 12) ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-center">
                <h6 class="fw-bold small text-muted mb-3 text-center"><?= e($chart['title']) ?></h6>
                <div class="chart-container" style="position:relative; width:100%; max-width:260px;">
                    <canvas id="chart-<?= $i ?>"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <?php
                        $headers = match($activeType) {
                            'tasks' => ['Task #', 'Title', 'Status', 'Priority', 'Department', 'Assigned To', 'Due Date', 'Created At'],
                            'users' => ['Employee ID', 'Name', 'Email', 'Role', 'Department', 'Status', 'Tasks Assigned', 'Tasks Created', 'Created At'],
                            'performance' => ['Name', 'Total Tasks', 'Completed', 'Overdue', 'Avg Time (Hrs)'],
                            'departments' => ['Department', 'Total Tasks', 'Completed', 'Pending', 'In Progress', 'Active Users', 'Total Users'],
                            'activity' => ['Date/Time', 'User', 'Action', 'Module', 'IP Address', 'Device'],
                            'login' => ['Date/Time', 'User', 'Email', 'Status', 'IP Address', 'User Agent'],
                            'audit' => ['Date/Time', 'User', 'Email', 'Action', 'Module', 'IP Address'],
                            'notifications' => ['Date/Time', 'User', 'Type', 'Title', 'Status'],
                            default => [],
                        };
                        ?>
                        <?php foreach ($headers as $h): ?>
                        <th class="fw-medium text-nowrap small"><?= e($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reportData)): ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php
                            $cells = match($activeType) {
                                'tasks' => [
                                    e($row->task_number ?? ''),
                                    e($row->title ?? ''),
                                    statusBadge($row->status ?? ''),
                                    priorityBadge($row->priority ?? ''),
                                    e($row->department_name ?? ''),
                                    e(($row->assigned_first_name ?? '') . ' ' . ($row->assigned_last_name ?? '')),
                                    formatDate($row->due_date ?? null),
                                    formatDate($row->created_at ?? null),
                                ],
                                'users' => [
                                    e($row->employee_id ?? ''),
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    e($row->email ?? ''),
                                    e($row->role_name ?? ''),
                                    e($row->department_name ?? ''),
                                    statusBadge(ucfirst($row->status ?? '')),
                                    e($row->tasks_assigned ?? 0),
                                    e($row->tasks_created ?? 0),
                                    formatDate($row->created_at ?? null),
                                ],
                                'performance' => [
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    e($row->total_tasks ?? 0),
                                    e($row->completed_tasks ?? 0),
                                    e($row->overdue_tasks ?? 0),
                                    e($row->avg_completion_hours ?? 'N/A'),
                                ],
                                'departments' => [
                                    e($row->department_name ?? ''),
                                    e($row->total_tasks ?? 0),
                                    e($row->completed_tasks ?? 0),
                                    e($row->pending_tasks ?? 0),
                                    e($row->in_progress_tasks ?? 0),
                                    e($row->active_users ?? 0),
                                    e($row->total_users ?? 0),
                                ],
                                'activity' => [
                                    formatDate($row->created_at ?? null, 'M j, Y g:i A'),
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    e($row->action ?? ''),
                                    '<span class="badge bg-info bg-opacity-10 text-info px-2 py-1">' . e($row->module ?? '') . '</span>',
                                    '<code class="small">' . e($row->ip_address ?? '') . '</code>',
                                    '<i class="fas ' . ($row->device === 'Mobile' ? 'fa-mobile-alt' : ($row->device === 'Tablet' ? 'fa-tablet-alt' : 'fa-desktop')) . ' me-1 text-muted"></i>' . e($row->device ?? 'Desktop'),
                                ],
                                'login' => [
                                    formatDate($row->created_at ?? null, 'M j, Y g:i A'),
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    e($row->email ?? ''),
                                    statusBadge(ucfirst($row->status ?? '')),
                                    '<code class="small">' . e($row->ip_address ?? '') . '</code>',
                                    '<span class="text-truncate d-inline-block" style="max-width:150px;" title="' . e($row->user_agent ?? '') . '">' . e(mb_strimwidth($row->user_agent ?? '-', 0, 40, '...')) . '</span>',
                                ],
                                'audit' => [
                                    formatDate($row->created_at ?? null, 'M j, Y g:i A'),
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    e($row->email ?? ''),
                                    e($row->action ?? ''),
                                    '<span class="badge bg-info bg-opacity-10 text-info px-2 py-1">' . e($row->module ?? '') . '</span>',
                                    '<code class="small">' . e($row->ip_address ?? '') . '</code>',
                                ],
                                'notifications' => [
                                    formatDate($row->created_at ?? null, 'M j, Y g:i A'),
                                    e(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                    '<span class="badge bg-' . ($row->type === 'success' ? 'success' : ($row->type === 'warning' ? 'warning' : ($row->type === 'danger' ? 'danger' : 'primary'))) . ' bg-opacity-10 text-' . ($row->type === 'success' ? 'success' : ($row->type === 'warning' ? 'warning' : ($row->type === 'danger' ? 'danger' : 'primary'))) . ' px-2 py-1">' . e($row->type ?? '') . '</span>',
                                    e($row->title ?? ''),
                                    !empty($row->is_read) ? '<span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Read</span>' : '<span class="badge bg-primary px-2 py-1">Unread</span>',
                                ],
                                default => [],
                            };
                            ?>
                            <?php foreach ($cells as $cell): ?>
                            <td class="text-nowrap"><?= $cell ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($headers) ?>" class="text-center py-5">
                                <div class="py-4">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3 d-block" style="opacity:0.3;"></i>
                                    <h6 class="fw-bold mb-1">No Data Found</h6>
                                    <p class="text-muted small mb-0">Try adjusting your filters or selecting a different date range.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Email Export Modal -->
<div class="modal fade" id="emailExportModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/reports/<?= $activeType ?>/export/email">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <?php foreach (($filters ?? []) as $fk => $fv): ?>
                    <?php if ($fv !== '' && $fv !== null): ?>
                    <input type="hidden" name="filters[<?= e($fk) ?>]" value="<?= e($fv) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="fw-bold modal-title"><i class="fas fa-envelope text-primary me-1"></i>Email Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Recipient Email</label>
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="email@example.com">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium text-muted">Format</label>
                        <select name="format" class="form-select form-select-sm">
                            <option value="csv">CSV</option>
                            <option value="xlsx">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane me-1"></i>Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart Initialization -->
<?php if (!empty($chartData)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = <?= json_encode($chartData) ?>;
    chartData.forEach(function (chart, i) {
        const canvas = document.getElementById('chart-' + i);
        if (!canvas) return;
        const total = chart.data.reduce(function (a, b) { return a + b; }, 0);
        if (total === 0) return;
        new Chart(canvas.getContext('2d'), {
            type: chart.type,
            data: {
                labels: chart.labels,
                datasets: [{
                    data: chart.data,
                    backgroundColor: chart.colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: { size: 10 },
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.parsed;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
});
</script>
<?php endif; ?>
