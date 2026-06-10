<?php
/**
 * Dashboard Stat Card
 *
 * @param string      $icon    Font Awesome icon class (e.g. "fa-users")
 * @param string      $label   Card label
 * @param string|int  $count   Stat value
 * @param string|null $trend   Trend text (e.g. "+12%")
 * @param bool        $trendUp Whether the trend is positive (green) or negative (red)
 * @param string      $color   Bootstrap color class (primary, success, warning, danger, info)
 * @return string
 */
function dashboardCard($icon, $label, $count, $trend = null, $trendUp = true, $color = 'primary'): string
{
    ob_start();
?>
    <div class="card border-0 shadow-sm stat-card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium text-uppercase" style="font-size:11px; letter-spacing:1.2px;"><?= e($label) ?></div>
                    <div class="h3 mb-0 fw-bold mt-1"><?= e($count) ?></div>
                </div>
                <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" style="width:48px; height:48px; background-color: rgba(var(--bs-<?= $color ?>-rgb), 0.1);">
                    <i class="fas <?= $icon ?> text-<?= $color ?> fa-lg"></i>
                </div>
            </div>
            <?php if ($trend !== null): ?>
            <div class="mt-2 small fw-medium d-flex align-items-center gap-1">
                <i class="fas fa-<?= $trendUp ? 'arrow-up' : 'arrow-down' ?> text-<?= $trendUp ? 'success' : 'danger' ?>"></i>
                <span class="text-<?= $trendUp ? 'success' : 'danger' ?>"><?= e($trend) ?></span>
                <span class="text-muted ms-1">vs last month</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
