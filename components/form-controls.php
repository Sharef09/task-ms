<?php
/**
 * Reusable Form Controls
 *
 * All functions return HTML string.
 * Bootstrap 5 form controls with validation support.
 */

/**
 * Text / Email / Password / Number Input
 *
 * @param string $type        Input type (text, email, password, number, url, tel, date, datetime-local)
 * @param string $name        Input name attribute
 * @param string $label       Label text
 * @param string $value       Current value
 * @param array  $attrs       Additional HTML attributes ['class'=>'', 'placeholder'=>'', 'required'=>true, ...]
 * @param string $helpText    Help text below the input
 * @param string $error       Error message (shows invalid feedback)
 * @param bool   $floating    Use floating label style
 * @return string
 */
function input($type = 'text', $name = '', $label = '', $value = '', $attrs = [], $helpText = '', $error = '', $floating = false): string
{
    ob_start();
    $id       = $attrs['id'] ?? str_replace(['[',']'], '_', $name);
    $class    = 'form-control';
    $class   .= !empty($error) ? ' is-invalid' : '';
    $class   .= isset($attrs['class']) ? ' ' . $attrs['class'] : '';
    unset($attrs['class'], $attrs['id']);

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $attrsHtml .= ' ' . e($k);
        } elseif ($v !== false && $v !== null) {
            $attrsHtml .= ' ' . e($k) . '="' . e($v) . '"';
        }
    }

    if ($floating):
?>
        <div class="form-floating mb-3">
            <input type="<?= e($type) ?>" name="<?= e($name) ?>" id="<?= e($id) ?>" value="<?= e($value) ?>" class="<?= e($class) ?>"<?= $attrsHtml ?>>
            <label for="<?= e($id) ?>"><?= e($label) ?></label>
            <?php if (!empty($error)): ?>
                <div class="invalid-feedback"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($helpText)): ?>
                <div class="form-text"><?= e($helpText) ?></div>
            <?php endif; ?>
        </div>
<?php else: ?>
        <div class="mb-3">
            <?php if (!empty($label)): ?>
                <label for="<?= e($id) ?>" class="form-label small fw-medium"><?= e($label) ?></label>
            <?php endif; ?>
            <input type="<?= e($type) ?>" name="<?= e($name) ?>" id="<?= e($id) ?>" value="<?= e($value) ?>" class="<?= e($class) ?>"<?= $attrsHtml ?>>
            <?php if (!empty($error)): ?>
                <div class="invalid-feedback"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($helpText)): ?>
                <div class="form-text"><?= e($helpText) ?></div>
            <?php endif; ?>
        </div>
<?php
    endif;
    return ob_get_clean();
}

/**
 * Select Dropdown
 *
 * @param string $name        Select name
 * @param string $label       Label text
 * @param array  $options     ['value' => 'Display', ...] or [['value'=>'...','label'=>'...'], ...]
 * @param string $selected    Selected value
 * @param array  $attrs       Additional HTML attributes
 * @param string $placeholder Placeholder option (null to omit)
 * @param string $error       Error message
 * @return string
 */
function select($name = '', $label = '', $options = [], $selected = '', $attrs = [], $placeholder = 'Select...', $error = ''): string
{
    ob_start();
    $id       = $attrs['id'] ?? str_replace(['[',']'], '_', $name);
    $class    = 'form-select';
    $class   .= !empty($error) ? ' is-invalid' : '';
    $class   .= isset($attrs['class']) ? ' ' . $attrs['class'] : '';
    unset($attrs['class'], $attrs['id']);

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $attrsHtml .= ' ' . e($k);
        } elseif ($v !== false && $v !== null) {
            $attrsHtml .= ' ' . e($k) . '="' . e($v) . '"';
        }
    }
?>
    <div class="mb-3">
        <?php if (!empty($label)): ?>
            <label for="<?= e($id) ?>" class="form-label small fw-medium"><?= e($label) ?></label>
        <?php endif; ?>
        <select name="<?= e($name) ?>" id="<?= e($id) ?>" class="<?= e($class) ?>"<?= $attrsHtml ?>>
            <?php if ($placeholder !== null): ?>
                <option value=""><?= e($placeholder) ?></option>
            <?php endif; ?>
            <?php foreach ($options as $key => $opt):
                $val  = is_array($opt) ? ($opt['value'] ?? $key) : $key;
                $disp = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $key) : $opt;
            ?>
                <option value="<?= e($val) ?>" <?= (string)$selected === (string)$val ? 'selected' : '' ?>><?= e($disp) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($error)): ?>
            <div class="invalid-feedback"><?= e($error) ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Textarea
 *
 * @param string $name     Textarea name
 * @param string $label    Label text
 * @param string $value    Current value
 * @param array  $attrs    Additional attributes (rows, cols, placeholder, etc.)
 * @param string $error    Error message
 * @return string
 */
function textarea($name = '', $label = '', $value = '', $attrs = [], $error = ''): string
{
    ob_start();
    $id       = $attrs['id'] ?? str_replace(['[',']'], '_', $name);
    $class    = 'form-control';
    $class   .= !empty($error) ? ' is-invalid' : '';
    $class   .= isset($attrs['class']) ? ' ' . $attrs['class'] : '';
    unset($attrs['class'], $attrs['id']);

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $attrsHtml .= ' ' . e($k);
        } elseif ($v !== false && $v !== null) {
            $attrsHtml .= ' ' . e($k) . '="' . e($v) . '"';
        }
    }
?>
    <div class="mb-3">
        <?php if (!empty($label)): ?>
            <label for="<?= e($id) ?>" class="form-label small fw-medium"><?= e($label) ?></label>
        <?php endif; ?>
        <textarea name="<?= e($name) ?>" id="<?= e($id) ?>" class="<?= e($class) ?>"<?= $attrsHtml ?>><?= e($value) ?></textarea>
        <?php if (!empty($error)): ?>
            <div class="invalid-feedback"><?= e($error) ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * File Upload Input
 *
 * @param string $name     Input name
 * @param string $label    Label text
 * @param array  $attrs    Additional attributes (accept, multiple, etc.)
 * @param string $error    Error message
 * @param string $preview  Current file name (for edit forms)
 * @return string
 */
function fileUpload($name = '', $label = '', $attrs = [], $error = '', $preview = ''): string
{
    ob_start();
    $id       = $attrs['id'] ?? str_replace(['[',']'], '_', $name);
    $class    = 'form-control';
    $class   .= !empty($error) ? ' is-invalid' : '';
    $class   .= isset($attrs['class']) ? ' ' . $attrs['class'] : '';
    unset($attrs['class'], $attrs['id']);

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $attrsHtml .= ' ' . e($k);
        } elseif ($v !== false && $v !== null) {
            $attrsHtml .= ' ' . e($k) . '="' . e($v) . '"';
        }
    }
?>
    <div class="mb-3">
        <?php if (!empty($label)): ?>
            <label for="<?= e($id) ?>" class="form-label small fw-medium"><?= e($label) ?></label>
        <?php endif; ?>
        <input type="file" name="<?= e($name) ?>" id="<?= e($id) ?>" class="<?= e($class) ?>"<?= $attrsHtml ?>>
        <?php if (!empty($preview)): ?>
            <div class="mt-1 small text-muted">
                <i class="fas fa-paperclip me-1"></i>Current: <?= e($preview) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="invalid-feedback"><?= e($error) ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Toggle Switch (Bootstrap 5 form-check switch)
 *
 * @param string $name      Checkbox name
 * @param string $label     Label text
 * @param bool   $checked   Whether checked
 * @param array  $attrs     Additional attributes
 * @param string $helpText  Help text below
 * @return string
 */
function toggleSwitch($name = '', $label = '', $checked = false, $attrs = [], $helpText = ''): string
{
    ob_start();
    $id       = $attrs['id'] ?? str_replace(['[',']'], '_', $name);
    $class    = 'form-check-input';
    $class   .= isset($attrs['class']) ? ' ' . $attrs['class'] : '';
    unset($attrs['class'], $attrs['id']);

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $attrsHtml .= ' ' . e($k);
        } elseif ($v !== false && $v !== null) {
            $attrsHtml .= ' ' . e($k) . '="' . e($v) . '"';
        }
    }
?>
    <div class="form-check form-switch mb-3">
        <input class="<?= e($class) ?>" type="checkbox" name="<?= e($name) ?>" id="<?= e($id) ?>" value="1" <?= $checked ? 'checked' : '' ?> role="switch"<?= $attrsHtml ?>>
        <label class="form-check-label small fw-medium" for="<?= e($id) ?>"><?= e($label) ?></label>
        <?php if (!empty($helpText)): ?>
            <div class="form-text"><?= e($helpText) ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
