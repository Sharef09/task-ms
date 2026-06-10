<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$currentUser = session()->get('user');
$statuses = ['Submitted', 'Under Review', 'Approved', 'Implemented', 'Rejected'];
$statusColors = [
    'Submitted' => 'secondary',
    'Under Review' => 'info',
    'Approved' => 'success',
    'Implemented' => 'primary',
    'Rejected' => 'danger',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-lightbulb me-2"></i>Idea Details</h5>
    <div class="d-flex gap-1">
        <?php if ($idea->status === 'Submitted' && (isAdmin() || $currentUser->id == $idea->submitted_by)): ?>
        <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas/edit/<?= e($idea->id) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><?= e($idea->title) ?></h5>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($idea->category) ?></span>
                    <span class="badge bg-<?= $statusColors[$idea->status] ?? 'secondary' ?> bg-opacity-10 text-<?= $statusColors[$idea->status] ?? 'secondary' ?>"><?= e($idea->status) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-medium">Description</small>
                    <p class="mb-0 mt-1"><?= nl2br(e($idea->description ?? 'No description provided.')) ?></p>
                </div>
                <?php if (!empty($idea->review_notes)): ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted fw-medium">Review Notes</small>
                    <p class="mb-0 mt-1"><?= nl2br(e($idea->review_notes)) ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($idea->estimated_savings)): ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted fw-medium">Estimated Savings</small>
                    <p class="mb-0 mt-1 fw-bold text-success">$<?= number_format($idea->estimated_savings, 2) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <small class="text-muted fw-medium text-uppercase" style="font-size:11px;">Details</small>
                <ul class="list-unstyled mt-2 small">
                    <li class="mb-2">
                        <span class="text-muted">Submitted by:</span><br>
                        <span class="fw-medium"><?= e(($idea->submitter_first_name ?? '') . ' ' . ($idea->submitter_last_name ?? '')) ?></span>
                    </li>
                    <li class="mb-2">
                        <span class="text-muted">Submitted on:</span><br>
                        <span class="fw-medium"><?= date('M d, Y g:i A', strtotime($idea->created_at)) ?></span>
                    </li>
                    <?php if (!empty($idea->reviewed_by)): ?>
                    <li class="mb-2">
                        <span class="text-muted">Reviewed by:</span><br>
                        <span class="fw-medium"><?= e(($idea->reviewer_first_name ?? '') . ' ' . ($idea->reviewer_last_name ?? '')) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if ($idea->status !== 'Submitted'): ?>
                    <li class="mb-2">
                        <span class="text-muted">Last updated:</span><br>
                        <span class="fw-medium"><?= date('M d, Y g:i A', strtotime($idea->updated_at)) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (isAdmin()): ?>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body p-4">
                <small class="text-muted fw-medium text-uppercase" style="font-size:11px;">Review Idea</small>
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/employee-ideas/review/<?= e($idea->id) ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="status" required>
                            <option value="">Select Status</option>
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $idea->status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control form-control-sm" name="review_notes" rows="3" placeholder="Review notes"><?= e($idea->review_notes ?? '') ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium">Estimated Savings ($)</label>
                        <input type="number" class="form-control form-control-sm" name="estimated_savings" step="0.01" min="0" value="<?= e($idea->estimated_savings ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-check me-1"></i>Update Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
