<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Task Templates</h5>
    <?php if (!isStaff()): ?>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Create Task
    </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Template Name</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Created</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($templates)): ?>
                        <?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td class="ps-3"><?= e($tpl->id) ?></td>
                            <td class="fw-medium"><?= e($tpl->name ?? $tpl->template_name ?? '-') ?></td>
                            <td><div class="text-truncate" style="max-width:250px;"><?= e($tpl->description ?? '') ?></div></td>
                            <td><?= priorityBadge($tpl->default_priority ?? $tpl->priority ?? '') ?></td>
                            <td><?= e($tpl->category_name ?? '-') ?></td>
                            <td><?= e($tpl->department_name ?? '-') ?></td>
                            <td><?= formatDate($tpl->created_at ?? null, 'M j, Y') ?></td>
                            <td class="pe-3 text-end">
                                <a href="<?= rtrim($app['url'], '/') ?>/tasks/templates/create-from/<?= e($tpl->id) ?>" class="btn btn-sm btn-outline-primary" title="Create from template"><i class="fas fa-plus"></i> Use</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="fas fa-file-alt fa-2x mb-2 d-block"></i>No templates available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
