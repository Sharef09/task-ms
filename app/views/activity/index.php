<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$actionIcons = [
    'created' => 'fa-plus-circle text-success',
    'updated' => 'fa-edit text-info',
    'update'  => 'fa-edit text-info',
    'deleted' => 'fa-trash text-danger',
    'delete'  => 'fa-trash text-danger',
    'login'   => 'fa-sign-in-alt text-primary',
    'logout'  => 'fa-sign-out-alt text-secondary',
    'viewed'  => 'fa-eye text-secondary',
    'view'    => 'fa-eye text-secondary',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Activity Logs</h5>
    <span class="badge bg-secondary bg-opacity-10 text-secondary fs-6 px-3 py-2 rounded-pill">
        <i class="fas fa-list me-1"></i><?= e($total ?? 0) ?> Total
    </span>
</div>

<!-- Filter Bar -->
<form method="GET" action="<?= rtrim($app['url'], '/') ?>/activity-logs" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search users, actions, modules..." value="<?= e($filters['search'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="user_id">
            <option value="">All Users</option>
            <?php foreach ($users ?? [] as $user): ?>
            <option value="<?= e($user->id ?? '') ?>" <?= (!empty($filters['user_id']) && $filters['user_id'] == ($user->id ?? '')) ? 'selected' : '' ?>><?= e(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="action">
            <option value="">All Actions</option>
            <?php foreach ($actions ?? [] as $action): $val = is_object($action) ? ($action->action ?? '') : (is_array($action) ? ($action['action'] ?? '') : $action); ?>
            <option value="<?= e($val) ?>" <?= (!empty($filters['action']) && $filters['action'] === $val) ? 'selected' : '' ?>><?= e(ucfirst((string)$val)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="module">
            <option value="">All Modules</option>
            <?php foreach ($logModules ?? [] as $module): $val = is_object($module) ? ($module->module ?? '') : (is_array($module) ? ($module['module'] ?? '') : $module); ?>
            <option value="<?= e($val) ?>" <?= (!empty($filters['module']) && $filters['module'] === $val) ? 'selected' : '' ?>><?= e($val) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-1">
        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
    </div>
    <div class="col-md-1">
        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
    </div>
    <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Filter</button>
        <a href="<?= rtrim($app['url'], '/') ?>/activity-logs" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo"></i></a>
    </div>
</form>

<!-- Activity Timeline -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php echo '<!-- BEFORE_IF -->'; ?>
        <?php if (!empty($logs)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:200px;">User</th>
                        <th style="width:130px;">Action</th>
                        <th style="width:120px;">Module</th>
                        <th style="width:80px;">Record</th>
                        <th style="width:130px;">IP Address</th>
                        <th style="width:90px;">Device</th>
                        <th class="pe-3" style="width:170px;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="log-row" data-log-id="<?= e($log->id ?? '') ?>" style="cursor:pointer;">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:30px;height:30px;">
                                    <?php if (!empty($log->avatar)): ?>
                                    <img src="<?= e($log->avatar) ?>" class="rounded-circle" width="30" height="30" alt="">
                                    <?php else: ?>
                                    <i class="fas fa-user text-muted" style="font-size:12px;"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="fw-medium"><?= e(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: 'System' ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark fw-normal px-2 py-1">
                                <i class="fas <?= $actionIcons[$log->action ?? ''] ?? 'fa-circle text-secondary' ?> me-1"></i><?= e(ucfirst($log->action ?? '')) ?>
                            </span>
                        </td>
                        <td><span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><?= e($log->module ?? '') ?></span></td>
                        <td><code class="small"><?= e($log->record_id ?? '-') ?></code></td>
                        <td class="text-muted"><code class="small"><?= e($log->ip_address ?? '-') ?></code></td>
                        <td><i class="fas <?= $log->device === 'Mobile' ? 'fa-mobile-alt' : ($log->device === 'Tablet' ? 'fa-tablet-alt' : 'fa-desktop') ?> me-1 text-muted"></i><?= e($log->device ?? 'Desktop') ?></td>
                        <td class="pe-3 text-nowrap text-muted"><?= formatDate($log->created_at ?? null, 'M j, Y g:i A') ?></td>
                    </tr>
                    <tr class="log-details-row d-none" id="log-details-<?= e($log->id ?? '') ?>">
                        <td colspan="7" class="p-0" style="background:#f8fafc;">
                            <div class="px-4 py-3 border-top">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold small text-uppercase text-muted mb-2" style="letter-spacing:0.5px;font-size:11px;">
                                            <i class="fas fa-code-branch me-1"></i>Changes
                                        </h6>
                                        <?php
                                        $oldVal = $log->old_value ?? null;
                                        $newVal = $log->new_value ?? null;
                                        $oldParsed = $oldVal ? json_decode($oldVal) : null;
                                        $newParsed = $newVal ? json_decode($newVal) : null;
                                        $hasChanges = $oldParsed !== null || $newParsed !== null || !empty($oldVal) || !empty($newVal);
                                        ?>
                                        <?php if ($hasChanges): ?>
                                        <div class="border rounded-2 p-2 bg-white" style="max-height:180px;overflow:auto;">
                                            <pre class="mb-0 small" style="white-space:pre-wrap;font-size:12px;line-height:1.5;"><?php
                                            if ($oldParsed !== null && $oldParsed !== false) echo "— Old —\n" . json_encode($oldParsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
                                            elseif (!empty($oldVal)) echo "— Old —\n" . e($oldVal) . "\n\n";
                                            if ($newParsed !== null && $newParsed !== false) echo "— New —\n" . json_encode($newParsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                            elseif (!empty($newVal)) echo "— New —\n" . e($newVal);
                                            ?></pre>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted small mb-0 py-1"><i class="fas fa-minus-circle me-1"></i>No change details</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold small text-uppercase text-muted mb-2" style="letter-spacing:0.5px;font-size:11px;">
                                            <i class="fas fa-info-circle me-1"></i>Metadata
                                        </h6>
                                        <div class="border rounded-2 p-2 bg-white">
                                            <div class="row row-cols-2 g-2 small">
                                                <div class="col text-muted">Log ID</div>
                                                <div class="col"><code class="small">#<?= e($log->id ?? '') ?></code></div>
                                                <div class="col text-muted">User Agent</div>
                                                <div class="col"><span class="text-truncate d-block" style="max-width:200px;" title="<?= e($log->user_agent ?? '') ?>"><?= e(mb_strimwidth($log->user_agent ?? '-', 0, 60, '...')) ?></span></div>
                                                <div class="col text-muted">Date & Time</div>
                                                <div class="col"><?= formatDate($log->created_at ?? null, 'Y-m-d H:i:s') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-5">
            <div class="mb-3"><i class="fas fa-history fa-3x text-light"></i></div>
            <h6 class="fw-bold mb-1">No Activity Logs</h6>
            <p class="small mb-0">Activity logs will appear here as users interact with the system.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($logs) && ($totalPages ?? 1) > 1): ?>
    <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= ($totalPages ?? 1); $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/activity-logs?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['user_id']) ? '&user_id=' . urlencode($filters['user_id']) : '' ?><?= !empty($filters['action']) ? '&action=' . urlencode($filters['action']) : '' ?><?= !empty($filters['module']) ? '&module=' . urlencode($filters['module']) : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . urlencode($filters['date_from']) : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . urlencode($filters['date_to']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.log-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var id = this.getAttribute('data-log-id');
            var detailsRow = document.getElementById('log-details-' + id);
            if (detailsRow) {
                var isHidden = detailsRow.classList.contains('d-none');
                document.querySelectorAll('.log-details-row').forEach(function(r) { r.classList.add('d-none'); });
                if (isHidden) {
                    detailsRow.classList.remove('d-none');
                }
            }
        });
    });
});
</script>