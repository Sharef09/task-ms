<?php
/**
 * Main Layout
 * 
 * Extracts:
 *   $pageTitle   - Page title string
 *   $content     - File path to the content view to include
 *   $breadcrumbs - (optional) Array of ['label' => '...', 'url' => '...']
 */
$app = require dirname(__DIR__) . '/config/app.php';
$pageTitle  = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];
require_once dirname(__DIR__) . '/components/alert.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require dirname(__DIR__) . '/includes/header.php'; ?>
    <title><?= e($pageTitle) ?> | <?= e($app['name']) ?></title>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">
            <!-- Top Navigation -->
            <?php require dirname(__DIR__) . '/includes/top-navigation.php'; ?>

            <!-- Main Content -->
            <main class="flex-fill p-4">
                <div class="container-fluid">
                    <?php if (!empty($breadcrumbs)): ?>
                        <?php require dirname(__DIR__) . '/components/breadcrumbs.php'; ?>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0 page-title"><?= e($pageTitle) ?></h4>
                    </div>

                    <?php if ($flash = flash('success')): ?>
                        <?= alert('success', $flash) ?>
                    <?php endif; ?>
                    <?php if ($flash = flash('error')): ?>
                        <?= alert('danger', $flash) ?>
                    <?php endif; ?>
                    <?php if ($flash = flash('warning')): ?>
                        <?= alert('warning', $flash) ?>
                    <?php endif; ?>
                    <?php if ($flash = flash('info')): ?>
                        <?= alert('info', $flash) ?>
                    <?php endif; ?>

                    <?php if (isset($content) && file_exists($content)): ?>
                        <?php require $content; ?>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-top px-4 py-3 text-muted small">
                &copy; <?= date('Y') ?> <?= e($app['name']) ?>. All rights reserved.
            </footer>
        </div>
    </div>

    <!-- Modals -->
    <div id="modal-container"></div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
