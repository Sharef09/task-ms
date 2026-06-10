<?php
/**
 * Statistics Widget Wrapper
 *
 * Renders a row of dashboardCard components.
 *
 * @param array $cards Array of card configs, each:
 *   ['icon' => 'fa-users', 'label' => 'Users', 'count' => 42, 'trend' => '+5%', 'trendUp' => true, 'color' => 'primary']
 * @param int   $cols  Number of columns per row (1-6)
 * @return string
 */
function statisticsWidget($cards = [], $cols = 4): string
{
    if (empty($cards)) return '';

    ob_start();
    $colClass = match ($cols) {
        1 => 'col-md-12',
        2 => 'col-md-6',
        3 => 'col-md-4',
        4 => 'col-lg-3 col-md-6',
        5 => 'col-lg-2 col-md-4',
        6 => 'col-lg-2 col-md-4',
        default => 'col-lg-3 col-md-6',
    };
?>
    <div class="row g-3 mb-4">
        <?php foreach ($cards as $card):
            $icon    = $card['icon'] ?? 'fa-chart-bar';
            $label   = $card['label'] ?? '';
            $count   = $card['count'] ?? 0;
            $trend   = $card['trend'] ?? null;
            $trendUp = $card['trendUp'] ?? true;
            $color   = $card['color'] ?? 'primary';
        ?>
            <div class="<?= $colClass ?>">
                <?= dashboardCard($icon, $label, $count, $trend, $trendUp, $color) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}
