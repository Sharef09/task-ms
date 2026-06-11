<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '/';
$appUrl     = rtrim((require dirname(__DIR__) . '/config/app.php')['url'], '/');

$modules = [
    ['label' => 'DASHBOARD', 'items' => [
        ['label' => 'Dashboard',       'icon' => 'fa-chart-pie', 'url' => '/dashboard',       'guard' => null],
    ]],
    ['label' => 'TASK MANAGEMENT', 'items' => [
        ['label' => 'Task Management',  'icon' => 'fa-tasks',     'url' => '/tasks',           'guard' => null],
    ]],
    ['label' => 'MEETINGS', 'items' => [
        ['label' => 'Meetings',         'icon' => 'fa-users',     'url' => '/meetings',        'guard' => null],
    ]],
    ['label' => 'EMPLOYEE IDEAS', 'items' => [
        ['label' => 'Employee Ideas',   'icon' => 'fa-lightbulb', 'url' => '/employee-ideas',  'guard' => null],
    ]],
    ['label' => 'ADMINISTRATION', 'items' => [
        ['label' => 'User Management',     'icon' => 'fa-users',      'url' => '/users',           'guard' => 'isAdmin'],
        ['label' => 'Role Management',     'icon' => 'fa-user-tag',   'url' => '/roles',           'guard' => 'isAdmin'],
        ['label' => 'Permission Management', 'icon' => 'fa-shield-alt', 'url' => '/permissions',   'guard' => 'isAdmin'],
        ['label' => 'Department Management', 'icon' => 'fa-building',  'url' => '/departments',     'guard' => 'isAdmin'],
    ]],
    ['label' => null, 'items' => [
        ['label' => 'Notifications',    'icon' => 'fa-bell',      'url' => '/notifications',   'guard' => null],
        ['label' => 'My Files',         'icon' => 'fa-folder',    'url' => '/user-files',      'guard' => null],
        ['label' => 'Reports',          'icon' => 'fa-file-alt',  'url' => '/reports',         'guard' => 'isAdmin'],
        ['label' => 'Activity Logs',    'icon' => 'fa-history',  'url' => '/activity-logs',   'guard' => 'isAdmin'],
        ['label' => 'Database Backup',  'icon' => 'fa-database',  'url' => '/backups',         'guard' => 'isAdmin'],
        ['label' => 'System Settings',  'icon' => 'fa-cog',       'url' => '/settings',        'guard' => 'isAdmin'],
    ]],
    ['label' => null, 'items' => [
        ['label' => 'My Profile',       'icon' => 'fa-user',      'url' => '/profile',         'guard' => null],
        ['label' => 'Logout',           'icon' => 'fa-sign-out-alt', 'url' => '/logout',       'guard' => null],
    ]],
];
?>
<nav class="sidebar border-end bg-dark" id="sidebar">
    <div class="sidebar-header d-flex align-items-center px-3 py-3 border-bottom border-secondary">
        <i class="fas fa-tasks text-primary me-2 fa-lg"></i>
        <span class="fw-bold text-white sidebar-brand">TaskMS</span>
    </div>

    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <?php foreach ($modules as $section): ?>
                <?php if (!empty($section['label'])): ?>
                    <li class="nav-section-label"><?= e($section['label']) ?></li>
                <?php endif; ?>

                <?php foreach ($section['items'] as $item):
                    $allowed = true;
                    if ($item['guard'] === 'isAdmin') {
                        $allowed = isAdmin();
                    } elseif ($item['guard'] !== null) {
                        $allowed = hasPermission($item['guard']);
                    }
                    if (!$allowed) continue;

                    $seg = ltrim($item['url'], '/');
                    $active = ($currentUri === '/' . $seg || strpos($currentUri, '/' . $seg . '/') === 0) ? 'active' : '';
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active ?>" href="<?= $appUrl . $item['url'] ?>"<?= $item['url'] === '/logout' ? ' data-confirm="Are you sure you want to logout?"' : '' ?>>
                            <i class="fas <?= $item['icon'] ?> fa-fw sidebar-icon"></i>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
