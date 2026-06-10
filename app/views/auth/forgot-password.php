<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="text-center mb-4">
    <h4 class="fw-bold">Forgot Password</h4>
    <p class="text-muted small">Enter your email address and we'll send you a password reset OTP</p>
</div>

<form method="POST" action="<?= rtrim($app['url'], '/') ?>/forgot-password">
    <?= csrf_field() ?>

    <div class="mb-4">
        <label for="email" class="form-label">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?= e(old('email')) ?>" required autofocus>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-medium mb-3">Send OTP</button>

    <div class="text-center">
        <a href="<?= rtrim($app['url'], '/') ?>/login" class="small text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
    </div>
</form>
