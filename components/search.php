<?php
/**
 * Search Bar Component
 *
 * @param string $id          Search form id
 * @param string $placeholder Search input placeholder
 * @param string $url         Form action URL
 * @param array  $filters     Optional filter controls: [['key' => 'status', 'label' => 'Status', 'options' => ['Active','Inactive']], ...]
 * @param string $searchTerm  Current search term
 * @return string
 */
function searchBar($id = 'search-form', $placeholder = 'Search...', $url = '', $filters = [], $searchTerm = ''): string
{
    ob_start();
    $currentParams = $_GET;
?>
    <form id="<?= e($id) ?>" action="<?= e($url) ?>" method="GET" class="mb-3">
        <div class="row g-2 align-items-center">
            <!-- Search Input -->
            <div class="<?= !empty($filters) ? 'col-md-4' : 'col-md-6' ?>">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        name="search"
                        placeholder="<?= e($placeholder) ?>"
                        value="<?= e($searchTerm ?: ($currentParams['search'] ?? '')) ?>"
                    >
                    <?php if (!empty($searchTerm) || !empty($currentParams['search'])): ?>
                        <a href="<?= e($url) ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filter Dropdowns -->
            <?php foreach ($filters as $filter):
                $key     = $filter['key'] ?? '';
                $label   = $filter['label'] ?? ucfirst($key);
                $options = $filter['options'] ?? [];
                $currentVal = $currentParams[$key] ?? '';
            ?>
                <div class="col-md-auto">
                    <select name="<?= e($key) ?>" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All <?= e($label) ?></option>
                        <?php foreach ($options as $opt):
                            $val = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt;
                            $lab = is_array($opt) ? ($opt['label'] ?? $val) : $opt;
                        ?>
                            <option value="<?= e($val) ?>" <?= $currentVal === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <!-- Submit / Per Page -->
            <div class="col-md-auto ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary" type="submit">
                    <i class="fas fa-search me-1"></i>Search
                </button>
            </div>
        </div>
    </form>
<?php
    return ob_get_clean();
}
