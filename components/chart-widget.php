<?php
/**
 * Chart.js Widget Wrapper
 *
 * @param string $id        Canvas element id
 * @param string $type      Chart type (line, bar, doughnut, pie, polarArea)
 * @param array  $labels    X-axis labels
 * @param array  $datasets  Chart.js dataset arrays: [['label'=>'...','data'=>[...],'backgroundColor'=>'...',...]]
 * @param array  $options   Additional Chart.js options
 * @param string $title     Optional card title
 * @param int    $height    Canvas height
 * @return string
 */
function chartWidget($id, $type, $labels, $datasets, $options = [], $title = '', $height = 300): string
{
    ob_start();
?>
    <div class="card border-0 shadow-sm h-100">
        <?php if (!empty($title)): ?>
            <div class="card-header bg-white border-bottom-0 pb-0">
                <h6 class="fw-bold mb-0"><?= e($title) ?></h6>
            </div>
        <?php endif; ?>
        <div class="card-body">
            <canvas id="<?= e($id) ?>" height="<?= e($height) ?>"></canvas>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('<?= e($id) ?>');
        if (!ctx) return;

        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 16,
                        font: { size: 12 }
                    }
                }
            }<?php if (in_array($type, ['line', 'bar', 'radar'])): ?>,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
<?php endif; ?>
        };

        const mergedOptions = deepMerge(defaultOptions, <?= json_encode($options) ?>);

        new Chart(ctx, {
            type: '<?= e($type) ?>',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: <?= json_encode($datasets) ?>
            },
            options: mergedOptions
        });
    });

    function deepMerge(target, source) {
        const result = JSON.parse(JSON.stringify(target));
        if (!source) return result;
        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                result[key] = deepMerge(result[key] || {}, source[key]);
            } else {
                result[key] = source[key];
            }
        }
        return result;
    }
    </script>
<?php
    return ob_get_clean();
}
