<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$schedule = $scheduledSettings ?? null;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-database me-2"></i>Database Backup</h5>
    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/backups/create" class="d-inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-sm fw-medium" id="createBackupBtn">
            <i class="fas fa-plus-circle me-1"></i>Create Backup
        </button>
    </form>
</div>

<div class="row g-4">
    <!-- Schedule Settings -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2"></i>Schedule Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/backups/schedule">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="enableSchedule" name="enabled" value="1" <?= !empty($schedule->enabled) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="enableSchedule">Enable Scheduled Backups</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Frequency</label>
                        <select class="form-select form-select-sm" name="frequency" id="backupFrequency">
                            <option value="daily" <?= ($schedule->frequency ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($schedule->frequency ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($schedule->frequency ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Time</label>
                        <input type="time" class="form-control form-control-sm" name="backup_time" value="<?= e($schedule->backup_time ?? '02:00') ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-save me-1"></i>Save Schedule</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Backup History -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Backup History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">File Name</th>
                                <th>Size</th>
                                <th>Type</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $items = $backups['items'] ?? ($backups ?? []); ?>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $backup): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-file-archive text-warning"></i>
                                            <span class="fw-medium"><?= e($backup->file_name ?? '') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $size = $backup->file_size ?? 0;
                                        if ($size >= 1073741824) {
                                            echo e(round($size / 1073741824, 2) . ' GB');
                                        } elseif ($size >= 1048576) {
                                            echo e(round($size / 1048576, 2) . ' MB');
                                        } elseif ($size >= 1024) {
                                            echo e(round($size / 1024, 2) . ' KB');
                                        } else {
                                            echo e($size . ' B');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (($backup->type ?? '') === 'scheduled'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info">Scheduled</span>
                                        <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">Manual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($backup->created_by_name ?? $backup->first_name . ' ' . $backup->last_name ?? '-') ?></td>
                                    <td class="text-nowrap"><?= formatDate($backup->created_at ?? null, 'M j, Y g:i A') ?></td>
                                    <td class="pe-3 text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="<?= rtrim($app['url'], '/') ?>/backups/download/<?= e($backup->id) ?>" class="btn btn-sm btn-outline-success" title="Download"><i class="fas fa-download"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Restore" data-restore-backup="<?= e($backup->id) ?>" data-name="<?= e($backup->file_name ?? '') ?>"><i class="fas fa-undo"></i></button>
                                            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/backups/delete/<?= e($backup->id) ?>" class="d-inline" onsubmit="return confirm('Delete backup <?= e($backup->file_name ?? '') ?>?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-database fa-2x mb-2 d-block"></i>
                                        No backups found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php $totalPages = $backups['pages'] ?? ($totalPages ?? 1); ?>
            <?php if (!empty($items) && $totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == ($currentPage ?? 1)) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/backups?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Confirm Restore</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-danger py-2 px-3 mb-0 small">
                    <i class="fas fa-exclamation-circle me-1"></i>Are you sure? This will overwrite the current database.
                </div>
                <p class="small text-muted mt-2 mb-0">Restore backup: <strong id="restoreBackupName"></strong></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="restoreBackupForm" style="display:none;">
                    <?= csrf_field() ?>
                </form>
                <button type="button" class="btn btn-sm btn-warning" id="confirmRestoreBtn"><i class="fas fa-undo me-1"></i>Restore</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var createBtn = document.getElementById('createBackupBtn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Creating...';
        });
    }

    var restoreId = null;
    document.querySelectorAll('[data-restore-backup]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            restoreId = this.getAttribute('data-restore-backup');
            document.getElementById('restoreBackupName').textContent = this.getAttribute('data-name');
            new bootstrap.Modal(document.getElementById('restoreBackupModal')).show();
        });
    });

    document.getElementById('confirmRestoreBtn').addEventListener('click', function() {
        if (restoreId) {
            var form = document.getElementById('restoreBackupForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/backups/restore/' + restoreId;
            form.style.display = 'block';
            form.submit();
        }
    });
});
</script>
