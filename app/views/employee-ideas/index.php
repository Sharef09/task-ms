<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$statuses = ['Submitted', 'Under Review', 'Approved', 'Implemented', 'Rejected'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-lightbulb me-2"></i>Employee Ideas</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Submit Idea
    </a>
</div>

<form method="GET" action="<?= rtrim($app['url'], '/') ?>/employee-ideas" class="row g-2 mb-4">
    <div class="col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" placeholder="Search ideas..." value="<?= e($filters['search'] ?? '') ?>">
        </div>
    </div>
    <?php if (isAdmin()): ?>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="status">
            <option value="">All Status</option>
            <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= (!empty($filters['status']) && $filters['status'] === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
        <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Title</th>
                        <th>Category</th>
                        <th>Submitted By</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ideas)): ?>
                        <?php foreach ($ideas as $idea): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-medium text-truncate" style="max-width:250px;"><?= e($idea->title) ?></div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($idea->category) ?></span></td>
                            <td><?= e(($idea->submitter_first_name ?? '') . ' ' . ($idea->submitter_last_name ?? '')) ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'Submitted' => 'secondary',
                                    'Under Review' => 'info',
                                    'Approved' => 'success',
                                    'Implemented' => 'primary',
                                    'Rejected' => 'danger',
                                ];
                                $color = $statusColors[$idea->status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>"><?= e($idea->status) ?></span>
                            </td>
                            <td class="text-muted"><?= date('M d, Y', strtotime($idea->created_at)) ?></td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas/view/<?= e($idea->id) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                    <?php if ($idea->status === 'Submitted' && (isAdmin() || (session()->has('user') && session()->get('user')->id == $idea->submitted_by))): ?>
                                    <a href="<?= rtrim($app['url'], '/') ?>/employee-ideas/edit/<?= e($idea->id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-lightbulb fa-2x mb-2 d-block"></i>
                                No ideas found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($ideas) && $totalPages > 1): ?>
    <div class="card-footer bg-white border-top py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/employee-ideas?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
