<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-folder-open me-2"></i>My Files</h5>
    <button type="button" class="btn btn-primary btn-sm fw-medium" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
        <i class="fas fa-upload me-1"></i>Upload File
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">File Name</th>
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
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file text-muted"></i>
                                    <div class="fw-medium text-truncate" style="max-width:300px;"><?= e($file->original_name) ?></div>
                                </div>
                            </td>
                            <td><code class="small"><?= e($file->mime_type ?? '-') ?></code></td>
                            <td>
                                <?php
                                $size = (int)($file->file_size ?? 0);
                                if ($size >= 1048576) {
                                    echo number_format($size / 1048576, 1) . ' MB';
                                } elseif ($size >= 1024) {
                                    echo number_format($size / 1024, 1) . ' KB';
                                } else {
                                    echo $size . ' B';
                                }
                                ?>
                            </td>
                            <td class="text-muted"><?= date('M d, Y', strtotime($file->created_at)) ?></td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= rtrim($app['url'], '/') ?>/user-files/download/<?= e($file->id) ?>" class="btn btn-sm btn-outline-primary" title="Download"><i class="fas fa-download"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-delete-file="<?= e($file->id) ?>" data-name="<?= e($file->original_name) ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                No files uploaded yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-upload me-1"></i>Upload File</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/user-files/upload" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="file" class="form-label small fw-medium">Choose File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="file" name="file" required>
                        <div class="form-text small text-muted">Allowed: PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, ZIP, TXT (max 10MB)</div>
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

<form method="POST" id="deleteFileForm" style="display:none;">
    <?= csrf_field() ?>
</form>

<div class="modal fade" id="deleteFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 small">Are you sure you want to delete <strong id="deleteFileName"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteFileBtn"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteFileId = null;
    document.querySelectorAll('[data-delete-file]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteFileId = this.getAttribute('data-delete-file');
            document.getElementById('deleteFileName').textContent = this.getAttribute('data-name');
            new bootstrap.Modal(document.getElementById('deleteFileModal')).show();
        });
    });
    document.getElementById('confirmDeleteFileBtn').addEventListener('click', function() {
        if (deleteFileId) {
            const form = document.getElementById('deleteFileForm');
            form.action = '<?= rtrim($app['url'], '/') ?>/user-files/delete/' + deleteFileId;
            form.submit();
        }
    });
});
</script>
