<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<form method="POST" action="<?= rtrim($app['url'], '/') ?>/login" class="needs-validation" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="username" class="form-label">Username or Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username or email" value="<?= e(old('username')) ?>" required autofocus>
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
            <label class="form-check-label small" for="remember">Remember Me</label>
        </div>
        <a href="<?= rtrim($app['url'], '/') ?>/forgot-password" class="small fw-medium text-decoration-none" style="color:#2563eb;">Forgot Password?</a>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Sign In
    </button>

    <p class="text-center mt-4 mb-0 small text-muted">
        Having trouble signing in? Contact your system administrator.
    </p>
</form>
