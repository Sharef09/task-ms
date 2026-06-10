<?php
/**
 * Bootstrap Modal Component
 *
 * @param string $id    Modal HTML id
 * @param string $title Modal title
 * @param string $body  Modal body HTML
 * @param string $footer Modal footer HTML (optional)
 * @param string $size  Bootstrap modal size class (modal-sm, modal-lg, modal-xl)
 * @return string
 */
function modal($id, $title, $body, $footer = '', $size = ''): string
{
    ob_start();
?>
    <div class="modal fade" id="<?= e($id) ?>" tabindex="-1" aria-labelledby="<?= e($id) ?>-label" aria-hidden="true">
        <div class="modal-dialog <?= e($size) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="<?= e($id) ?>-label"><?= $title ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= $body ?>
                </div>
                <?php if (!empty($footer)): ?>
                    <div class="modal-footer border-top-0 pt-0">
                        <?= $footer ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
