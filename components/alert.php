<?php
/**
 * Bootstrap Alert Component
 *
 * @param string $type        Alert type: success, danger, warning, info, primary, secondary
 * @param string $message     Alert message text
 * @param bool   $dismissible Whether to show the dismiss button
 * @return string
 */
function alert($type, $message, $dismissible = true): string
{
    if (empty($message)) return '';

    ob_start();
    $iconMap = [
        'success' => 'fa-check-circle',
        'danger'  => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info'    => 'fa-info-circle',
        'primary' => 'fa-bell',
        'secondary' => 'fa-clock',
    ];
    $icon = $iconMap[$type] ?? 'fa-info-circle';
?>
    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
        <i class="fas <?= $icon ?>"></i>
        <span><?= $message ?></span>
        <?php if ($dismissible): ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
