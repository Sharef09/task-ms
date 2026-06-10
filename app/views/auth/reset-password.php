<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="text-center mb-4">
    <h4 class="fw-bold">Reset Password</h4>
    <p class="text-muted small">Choose a strong new password for your account</p>
</div>

<form method="POST" action="<?= rtrim($app['url'], '/') ?>/reset-password">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= e($userId ?? '') ?>">

    <div class="mb-3">
        <label for="password" class="form-label">New Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required minlength="8">
        </div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
        </div>
    </div>

    <div class="alert alert-info bg-info bg-opacity-10 border-0 small py-2 mb-4" role="alert">
        <i class="fas fa-info-circle me-1"></i>
        Password must be at least 8 characters and include a mix of letters, numbers, and symbols.
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">Reset Password</button>
</form>
