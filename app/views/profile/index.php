<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$activeTab = $_GET['tab'] ?? 'personal';
?>
<ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'personal' ? 'active' : '' ?>" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="<?= $activeTab === 'personal' ? 'true' : 'false' ?>"><i class="fas fa-user me-1"></i>Personal Information</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'password' ? 'active' : '' ?>" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="<?= $activeTab === 'password' ? 'true' : 'false' ?>"><i class="fas fa-key me-1"></i>Change Password</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'notifications' ? 'active' : '' ?>" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="<?= $activeTab === 'notifications' ? 'true' : 'false' ?>"><i class="fas fa-bell me-1"></i>Notification Preferences</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'login-history' ? 'active' : '' ?>" id="login-history-tab" data-bs-toggle="tab" data-bs-target="#login-history" type="button" role="tab" aria-controls="login-history" aria-selected="<?= $activeTab === 'login-history' ? 'true' : 'false' ?>"><i class="fas fa-sign-in-alt me-1"></i>Login History</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'activity' ? 'active' : '' ?>" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="<?= $activeTab === 'activity' ? 'true' : 'false' ?>"><i class="fas fa-history me-1"></i>Activity History</button>
    </li>
</ul>

<div class="tab-content" id="profileTabContent">

    <!-- Personal Information -->
    <div class="tab-pane fade <?= $activeTab === 'personal' ? 'show active' : '' ?>" id="personal" role="tabpanel" aria-labelledby="personal-tab">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width:120px;height:120px;">
                                <?php if (!empty($user->avatar)): ?>
                                <img src="<?= rtrim($app['url'], '/') ?>/<?= e($user->avatar) ?>" class="rounded-circle" width="120" height="120" alt="Avatar">
                                <?php else: ?>
                                <i class="fas fa-user fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h5 class="fw-bold"><?= e(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></h5>
                        <p class="text-muted small mb-0"><?= e($user->email ?? '') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/profile/update" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">First Name</label>
                                    <input type="text" class="form-control form-control-sm" name="first_name" value="<?= e($user->first_name ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">Last Name</label>
                                    <input type="text" class="form-control form-control-sm" name="last_name" value="<?= e($user->last_name ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">Email</label>
                                    <input type="email" class="form-control form-control-sm" name="email" value="<?= e($user->email ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">Mobile</label>
                                    <input type="text" class="form-control form-control-sm" name="mobile" value="<?= e($user->mobile ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">Department</label>
                                    <input type="text" class="form-control form-control-sm" value="<?= e($user->department_name ?? '') ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium text-muted">Username</label>
                                    <input type="text" class="form-control form-control-sm" value="<?= e($user->username ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium text-muted">Profile Photo</label>
                                    <input type="file" class="form-control form-control-sm" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="tab-pane fade <?= $activeTab === 'password' ? 'show active' : '' ?>" id="password" role="tabpanel" aria-labelledby="password-tab">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-key me-2"></i>Change Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/profile/change-password">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label small fw-medium text-muted">Current Password</label>
                                <input type="password" class="form-control form-control-sm" name="current_password" required>
                            </div>
                                <div class="mb-3">
                                <label class="form-label small fw-medium text-muted">New Password</label>
                                <input type="password" class="form-control form-control-sm" name="new_password" required minlength="8">
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium text-muted">Confirm New Password</label>
                                <input type="password" class="form-control form-control-sm" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-key me-1"></i>Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Preferences -->
    <div class="tab-pane fade <?= $activeTab === 'notifications' ? 'show active' : '' ?>" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-bell me-2"></i>Notification Preferences</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/profile/notification-preferences">
                            <?= csrf_field() ?>
                            <div class="mb-4">
                                <h6 class="fw-bold small text-muted mb-3"><i class="fas fa-envelope me-1"></i>Email Notifications</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_notifications" name="email_notifications" value="1" <?= !empty($preferences['email_notifications']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="email_notifications">Email Notifications</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="task_assigned" name="task_assigned" value="1" <?= !empty($preferences['task_assigned']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="task_assigned">Task Assigned</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="task_completed" name="task_completed" value="1" <?= !empty($preferences['task_completed']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="task_completed">Task Completed</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="task_overdue" name="task_overdue" value="1" <?= !empty($preferences['task_overdue']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="task_overdue">Task Overdue</label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h6 class="fw-bold small text-muted mb-3"><i class="fas fa-browser me-1"></i>In-App Notifications</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="browser_notifications" name="browser_notifications" value="1" <?= !empty($preferences['browser_notifications']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="browser_notifications">In-App Notifications</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="mention_notifications" name="mention_notifications" value="1" <?= !empty($preferences['mention_notifications']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mention_notifications">Mentions</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login History -->
    <div class="tab-pane fade <?= $activeTab === 'login-history' ? 'show active' : '' ?>" id="login-history" role="tabpanel" aria-labelledby="login-history-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>IP Address</th>
                                <th>Browser</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loginHistory)): ?>
                                <?php foreach ($loginHistory as $login): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= formatDate($login->created_at ?? $login->login_at ?? null, 'M j, Y g:i A') ?></td>
                                    <td><code><?= e($login->ip_address ?? '-') ?></code></td>
                                    <td class="text-truncate" style="max-width:250px;"><?= e($login->user_agent ?? '-') ?></td>
                                    <td class="pe-3">
                                        <?php if (($login->status ?? '') === 'success'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">Success</span>
                                        <?php elseif (($login->status ?? '') === 'failed'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Failed</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e(ucfirst($login->status ?? 'Unknown')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-sign-in-alt fa-2x mb-2 d-block"></i>
                                        No login history found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($loginHistory) && ($loginTotalPages ?? 1) > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= ($loginTotalPages ?? 1); $i++): ?>
                        <li class="page-item <?= ($i == ($loginCurrentPage ?? 1)) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/profile?tab=login-history&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activity History -->
    <div class="tab-pane fade <?= $activeTab === 'activity' ? 'show active' : '' ?>" id="activity" role="tabpanel" aria-labelledby="activity-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>IP Address</th>
                                <th class="pe-3">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activityHistory)): ?>
                                <?php foreach ($activityHistory as $activity): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= formatDate($activity->created_at ?? null, 'M j, Y g:i A') ?></td>
                                    <td>
                                        <?php
                                        $actIcon = match($activity->action ?? '') {
                                            'created' => 'fa-plus-circle text-success',
                                            'updated', 'update' => 'fa-edit text-info',
                                            'deleted', 'delete' => 'fa-trash text-danger',
                                            default => 'fa-circle text-secondary',
                                        };
                                        ?>
                                        <span class="badge bg-light text-dark fw-normal">
                                            <i class="fas <?= $actIcon ?> me-1"></i><?= e(ucfirst($activity->action ?? '')) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-info bg-opacity-10 text-info"><?= e($activity->module ?? '') ?></span></td>
                                    <td><code><?= e($activity->ip_address ?? '-') ?></code></td>
                                    <td class="pe-3"><i class="fas fa-<?= ($activity->device ?? '') === 'Mobile' ? 'mobile-alt' : (($activity->device ?? '') === 'Tablet' ? 'tablet-alt' : 'desktop') ?> me-1"></i><?= e($activity->device ?? 'Desktop') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fas fa-history fa-2x mb-2 d-block"></i>
                                        No activity history found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($activityHistory) && ($activityTotalPages ?? 1) > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= ($activityTotalPages ?? 1); $i++): ?>
                        <li class="page-item <?= ($i == ($activityCurrentPage ?? 1)) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/profile?tab=activity&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
