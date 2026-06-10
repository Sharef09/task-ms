<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Meetings</h5>
    <div class="d-flex gap-2">
        <?php if (isAdmin() || isManager()): ?>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>New Meeting</a>
        <?php endif; ?>
        <a href="<?= rtrim($app['url'], '/') ?>/meetings/special-requests" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-file-pen me-1"></i>Special Requests
            <?php if (!empty($pendingRequests) && $pendingRequests > 0): ?>
            <span class="badge bg-danger ms-1"><?= e($pendingRequests) ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search meetings..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="Scheduled" <?= ($_GET['status'] ?? '') === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="Ongoing" <?= ($_GET['status'] ?? '') === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= ($_GET['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="department_id">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= e($dept->id) ?>" <?= ($_GET['department_id'] ?? '') == $dept->id ? 'selected' : '' ?>><?= e($dept->name) ?></option>
                    <?php endforeach; ?>
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
                        <th class="py-2">Department</th>
                        <th class="py-2">Organizer</th>
                        <th class="py-2">Date</th>
                        <th class="py-2">Location</th>
                        <th class="py-2">Status</th>
                        <th class="py-2 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($meetings)): ?>
                    <?php foreach ($meetings as $m): ?>
                    <tr>
                        <td class="fw-medium"><?= e($m->title) ?></td>
                        <td class="small"><?= e($m->department_name ?? '-') ?></td>
                        <td class="small"><?= e(($m->organizer_first_name ?? '') . ' ' . ($m->organizer_last_name ?? '')) ?></td>
                        <td class="small"><?= formatDate($m->start_date ?? null) ?></td>
                        <td class="small"><?= e($m->location ?? '-') ?></td>
                        <td><?= statusBadge($m->status ?? '') ?></td>
                        <td class="text-end">
                            <a href="<?= rtrim($app['url'], '/') ?>/meetings/view/<?= e($m->id) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <?php if (isAdmin() || isManager()): ?>
                            <a href="<?= rtrim($app['url'], '/') ?>/meetings/edit/<?= e($m->id) ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/meetings/delete/<?= e($m->id) ?>" class="d-inline" onsubmit="return confirm('Delete this meeting?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4 small">No meetings found</td></tr>
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
