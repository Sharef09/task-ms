<?php
/**
 * Auth Layout
 * 
 * Centered card layout for login, forgot password, OTP, reset password pages.
 * 
 * Extracts:
 *   $pageTitle - Page title string
 *   $content   - File path to the content view to include
 */
$app = require dirname(__DIR__) . '/config/app.php';
$pageTitle = $pageTitle ?? 'Authentication';
require_once dirname(__DIR__) . '/components/alert.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require dirname(__DIR__) . '/includes/header.php'; ?>
    <title><?= e($pageTitle) ?> | <?= e($app['name']) ?></title>
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8edf5 50%, #f0f4ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 1rem;
            margin: auto;
        }
        .auth-card {
            background: #fff;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(30, 42, 58, 0.10);
            overflow: hidden;
        }
        .auth-header {
            padding: 2.5rem 2rem 0 2rem;
            text-align: center;
        }
        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 36px;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
            letter-spacing: -0.3px;
        }
        .auth-brand-icon {
            width: 46px;
            height: 46px;
            background: #2563eb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
        }
        .auth-body {
            padding: 1.5rem 2rem 2rem 2rem;
        }
        .auth-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e2a3a;
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }
        .auth-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 0;
            text-align: center;
        }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 4px;
        }
        .form-control, .input-group-text {
            font-size: 14px;
            border-radius: 8px;
        }
        .input-group .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .input-group .input-group-text {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            background: #f8fafc;
            border-right: 0;
            color: #64748b;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }
        .btn-primary {
            background: #2563eb;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.30);
        }
        .auth-footer {
            text-align: center;
            padding: 0 2rem 2rem 2rem;
        }
        .auth-divider {
            border-top: 1px solid #e2e8f0;
            margin: 0 -2rem 1.5rem -2rem;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <h3 class="auth-brand">
                    <span class="auth-brand-icon"><i class="fas fa-tasks"></i></span>
                    <?= e($app['name']) ?>
                </h3>
                <div class="mt-3 mb-4">
                    <h1 class="auth-title"><?= e($pageTitle ?? 'Welcome Back') ?></h1>
                    <p class="auth-subtitle">Sign in to your account to continue</p>
                </div>
            </div>

            <?php if ($flash = flash('success')): ?>
                <div class="px-4"><?= alert('success', $flash) ?></div>
            <?php endif; ?>
            <?php if ($flash = flash('error')): ?>
                <div class="px-4"><?= alert('danger', $flash) ?></div>
            <?php endif; ?>
            <?php if ($flash = flash('warning')): ?>
                <div class="px-4"><?= alert('warning', $flash) ?></div>
            <?php endif; ?>
            <?php if ($flash = flash('info')): ?>
                <div class="px-4"><?= alert('info', $flash) ?></div>
            <?php endif; ?>

            <div class="auth-body">
                <?php if (isset($content) && file_exists($content)): ?>
                    <?php require $content; ?>
                <?php endif; ?>
            </div>

            <div class="auth-divider"></div>

            <div class="auth-footer">
                <p class="small text-muted mb-0">&copy; <?= date('Y') ?> <?= e($app['name']) ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
