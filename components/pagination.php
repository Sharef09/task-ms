<?php
/**
 * Pagination Component
 *
 * @param int    $currentPage Current active page (1-indexed)
 * @param int    $totalPages  Total number of pages
 * @param string $url         Base URL for page links (appends ?page=N or &page=N)
 * @return string
 */
function pagination($currentPage, $totalPages, $url): string
{
    if ($totalPages <= 1) return '';

    ob_start();

    $queryParams = $_GET;
    $hasQuery    = !empty($queryParams);

    function pageUrl($baseUrl, $queryParams, $page): string {
        $queryParams['page'] = $page;
        return $baseUrl . '?' . http_build_query($queryParams);
    }
?>
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <!-- Previous -->
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(pageUrl($url, $queryParams, $currentPage - 1)) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);

            if ($start > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= e(pageUrl($url, $queryParams, 1)) ?>">1</a>
                </li>
                <?php if ($start > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif;
            endif;

            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(pageUrl($url, $queryParams, $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor;

            if ($end < $totalPages):
                if ($end < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= e(pageUrl($url, $queryParams, $totalPages)) ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(pageUrl($url, $queryParams, $currentPage + 1)) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php
    return ob_get_clean();
}
