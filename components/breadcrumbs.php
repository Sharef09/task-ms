<?php
/**
 * Breadcrumb Trail Component
 *
 * Expects $breadcrumbs array: [['label' => 'Home', 'url' => '/dashboard'], ...]
 * or a flat array of label strings.
 * Also uses $pageTitle as the last (active) crumb when appropriate.
 */
$breadcrumbs = $breadcrumbs ?? [];
$appUrl      = rtrim((require dirname(__DIR__) . '/config/app.php')['url'], '/');
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item">
            <a href="<?= $appUrl ?>/dashboard" class="text-decoration-none">
                <i class="fas fa-home me-1"></i>Home
            </a>
        </li>
        <?php foreach ($breadcrumbs as $crumb):
            $label = is_array($crumb) ? ($crumb['label'] ?? '') : $crumb;
            $url   = is_array($crumb) ? ($crumb['url'] ?? null) : null;
        ?>
            <?php if ($url): ?>
                <li class="breadcrumb-item">
                    <a href="<?= e($url) ?>" class="text-decoration-none"><?= e($label) ?></a>
                </li>
            <?php else: ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
