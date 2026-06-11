<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '/';
$baseUrl = rtrim($app['url'], '/');

$links = [
    ['label' => 'All Tasks',   'icon' => 'fa-list',       'url' => '/tasks'],
    ['label' => 'My Tasks',    'icon' => 'fa-user-check', 'url' => '/tasks/my-tasks'],
    ['label' => 'Sent Tasks',  'icon' => 'fa-paper-plane','url' => '/tasks/sent-tasks'],
    ['label' => 'Abused Tasks','icon' => 'fa-exclamation-triangle', 'url' => '/tasks/abused-tasks'],
    ['label' => 'My Files',    'icon' => 'fa-folder',     'url' => '/tasks/my-files'],
];
?>
<div class="d-flex gap-1 mb-3 flex-wrap">
    <?php foreach ($links as $link):
        $active = str_replace($baseUrl, '', $currentUri) === $link['url'];
    ?>
        <a href="<?= $baseUrl . $link['url'] ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="fas <?= $link['icon'] ?> me-1"></i><?= $link['label'] ?>
        </a>
    <?php endforeach; ?>
</div>
