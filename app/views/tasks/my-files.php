<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0"><i class="fas fa-folder me-2"></i>My Files</h5>
    <?php if (!isStaff()): ?>
    <a href="<?= rtrim($app['url'], '/') ?>/tasks/create" class="btn btn-primary btn-sm fw-medium">
        <i class="fas fa-plus me-1"></i>Create Task
    </a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_nav.php' ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-folder-open me-1"></i> <?= e($folder ?: '/') ?></h6>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
            <i class="fas fa-upload me-1"></i>Upload File
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($files)): ?>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td class="ps-3"><i class="fas fa-file me-2 text-muted"></i><?= e($file->filename ?? $file->name ?? '-') ?></td>
                            <td><?= e($file->mime_type ?? $file->type ?? '-') ?></td>
                            <td><?= e(isset($file->file_size) ? number_format($file->file_size / 1024, 1) . ' KB' : '-') ?></td>
                            <td><?= formatDate($file->created_at ?? null, 'M j, Y') ?></td>
                            <td class="pe-3 text-end">
                                <a href="<?= rtrim($app['url'], '/') ?>/tasks/my-files/download/<?= e($file->id) ?>" class="btn btn-sm btn-outline-primary" title="Download"><i class="fas fa-download"></i></a>
                                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/my-files/delete/<?= e($file->id) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-folder-open fa-2x mb-2 d-block"></i>No files uploaded yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-upload me-1"></i>Upload File</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/tasks/my-files/upload" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Select File</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload me-1"></i>Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
