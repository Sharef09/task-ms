<?php
/**
 * Reusable Data Table
 *
 * @param string $id          Table HTML id
 * @param array  $columns     Array of ['key' => 'db_column', 'label' => 'Display Name', 'sortable' => true, 'class' => '']
 * @param array  $data        Array of stdClass or associative arrays
 * @param bool   $actions     Whether to show an actions column
 * @param array  $bulkActions Array of ['value' => 'action_name', 'label' => 'Action Label']
 * @param string $pageUrl     Base URL for the current page (for search/sort/pagination)
 * @return string
 */
function datatable($id, $columns, $data, $actions = true, $bulkActions = [], $pageUrl = ''): string
{
    ob_start();
    $sortBy  = $_GET['sort_by'] ?? '';
    $sortDir = $_GET['sort_dir'] ?? 'asc';
?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if (!empty($bulkActions)): ?>
            <div class="row align-items-center mb-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 bulk-actions-bar" style="display:none;">
                        <span class="small text-muted selected-count" id="<?= $id ?>-selected-count">0 selected</span>
                        <select class="form-select form-select-sm" style="width:auto;" id="<?= $id ?>-bulk-action">
                            <option value="">Bulk Actions</option>
                            <?php foreach ($bulkActions as $ba): ?>
                                <option value="<?= e($ba['value']) ?>"><?= e($ba['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary" id="<?= $id ?>-apply-bulk">Apply</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="<?= e($id) ?>">
                    <thead class="table-light">
                        <tr>
                            <?php if (!empty($bulkActions)): ?>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input checkbox-parent" type="checkbox" id="<?= $id ?>-select-all">
                                    </div>
                                </th>
                            <?php endif; ?>
                            <?php foreach ($columns as $col):
                                $key     = $col['key'] ?? '';
                                $label   = $col['label'] ?? ucfirst($key);
                                $sortable = $col['sortable'] ?? true;
                                $class   = $col['class'] ?? '';
                            ?>
                                <th class="<?= e($class) ?>">
                                    <?php if ($sortable && $key): ?>
                                        <a href="<?= e(buildSortUrl($pageUrl, $key, $sortBy, $sortDir)) ?>" class="text-dark text-decoration-none d-flex align-items-center gap-1">
                                            <?= e($label) ?>
                                            <?php if ($sortBy === $key): ?>
                                                <i class="fas fa-sort-<?= $sortDir === 'asc' ? 'up' : 'down' ?> text-primary small"></i>
                                            <?php else: ?>
                                                <i class="fas fa-sort text-muted small"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <?= e($label) ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <?php if ($actions): ?>
                                <th width="100" class="text-end">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="<?= count($columns) + ($actions ? 1 : 0) + (!empty($bulkActions) ? 1 : 0) ?>" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                    No records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $row):
                                $row = is_object($row) ? get_object_vars($row) : $row;
                                $rowId = $row['id'] ?? 0;
                            ?>
                                <tr>
                                    <?php if (!empty($bulkActions)): ?>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input checkbox-child" type="checkbox" name="selected_ids[]" value="<?= e($rowId) ?>">
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                    <?php foreach ($columns as $col):
                                        $key   = $col['key'] ?? '';
                                        $value = $row[$key] ?? '-';
                                        $class = $col['class'] ?? '';
                                        $render = $col['render'] ?? null;
                                    ?>
                                        <td class="<?= e($class) ?>">
                                            <?php if (is_callable($render)): ?>
                                                <?= $render($value, $row) ?>
                                            <?php else: ?>
                                                <?= e($value) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if ($actions): ?>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><a class="dropdown-item" href="<?= e($pageUrl . '/' . $rowId) ?>"><i class="fas fa-eye fa-fw me-2 text-muted"></i>View</a></li>
                                                    <li><a class="dropdown-item" href="<?= e($pageUrl . '/' . $rowId . '/edit') ?>"><i class="fas fa-edit fa-fw me-2 text-muted"></i>Edit</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger delete-btn" href="#" data-id="<?= e($rowId) ?>" data-url="<?= e($pageUrl . '/' . $rowId) ?>"><i class="fas fa-trash fa-fw me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Build sort URL helper.
 */
function buildSortUrl(string $baseUrl, string $key, string $currentSort, string $currentDir): string
{
    $params = $_GET;
    if ($currentSort === $key) {
        $params['sort_dir'] = $currentDir === 'asc' ? 'desc' : 'asc';
    } else {
        $params['sort_by']  = $key;
        $params['sort_dir'] = 'asc';
    }
    $qs = http_build_query($params);
    return $baseUrl . ($qs ? '?' . $qs : '');
}
