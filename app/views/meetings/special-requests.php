<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-file-pen me-2"></i>Special Meeting Requests</h5>
    <div>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings/special-requests/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>New Request</a>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="py-2">Title</th>
                        <th class="py-2">Requester</th>
                        <th class="py-2">Department</th>
                        <th class="py-2">Preferred Date</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Approved By</th>
                        <th class="py-2 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td class="fw-medium"><?= e($r->title) ?></td>
                        <td class="small"><?= e(($r->requester_first_name ?? '') . ' ' . ($r->requester_last_name ?? '')) ?></td>
                        <td class="small"><?= e($r->department_name ?? '-') ?></td>
                        <td class="small"><?= formatDate($r->preferred_date ?? null) ?></td>
                        <td><?= statusBadge($r->status ?? '') ?></td>
                        <td class="small"><?= e(($r->approver_first_name ?? '') . ' ' . ($r->approver_last_name ?? '')) ?: '-' ?></td>
                        <td class="text-end">
                            <?php if ($r->status === 'Pending' && isAdmin()): ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="approveRequest(<?= e($r->id) ?>)"><i class="fas fa-check"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectRequest(<?= e($r->id) ?>)"><i class="fas fa-times"></i></button>
                            <?php elseif ($r->status === 'Rejected' && !empty($r->rejection_reason)): ?>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="alert('<?= e(addslashes($r->rejection_reason)) ?>')"><i class="fas fa-info-circle"></i></button>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4 small">No requests found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
    <div class="card-footer bg-white border-top p-2">
        <?php require dirname(__DIR__, 2) . '/components/pagination.php'; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="" id="approveForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-success"><i class="fas fa-check-circle me-1"></i>Approve Request</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="small mb-2">Approve this special meeting request?</p>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="create_meeting" name="create_meeting" value="1" checked>
                        <label class="form-check-label small" for="create_meeting">Create meeting automatically</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="" id="rejectForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reject">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-danger"><i class="fas fa-times-circle me-1"></i>Reject Request</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label class="form-label small">Reason for rejection</label>
                        <textarea class="form-control form-control-sm" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times me-1"></i>Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(id) {
    document.getElementById('approveForm').action = '<?= rtrim($app['url'], '/') ?>/meetings/special-requests/approve/' + id;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function rejectRequest(id) {
    document.getElementById('rejectForm').action = '<?= rtrim($app['url'], '/') ?>/meetings/special-requests/approve/' + id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
<?php endif; ?>
