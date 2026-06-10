<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
$activeTab = $_GET['tab'] ?? 'general';
$timezoneIdentifiers = \DateTimeZone::listIdentifiers();
?>
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="<?= $activeTab === 'general' ? 'true' : 'false' ?>"><i class="fas fa-cog me-1"></i>General Settings</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'email' ? 'active' : '' ?>" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="<?= $activeTab === 'email' ? 'true' : 'false' ?>"><i class="fas fa-envelope me-1"></i>Email Settings</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="<?= $activeTab === 'security' ? 'true' : 'false' ?>"><i class="fas fa-shield-alt me-1"></i>Security Settings</button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">

    <!-- General Settings -->
    <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>" id="general" role="tabpanel" aria-labelledby="general-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-cog me-2"></i>General Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/settings/general" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">System Name</label>
                            <input type="text" class="form-control form-control-sm" name="app_name" value="<?= e($settings['app_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Company Name</label>
                            <input type="text" class="form-control form-control-sm" name="company_name" value="<?= e($settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Logo</label>
                            <input type="file" class="form-control form-control-sm" name="logo" accept="image/png,image/jpeg,image/gif,image/svg+xml">
                            <?php if (!empty($settings['logo'])): ?>
                            <div class="mt-2">
                                <img src="<?= rtrim($app['url'], '/') ?>/<?= e($settings['logo']) ?>" class="border rounded" style="max-height:60px;" alt="Logo Preview">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Favicon</label>
                            <input type="file" class="form-control form-control-sm" name="favicon" accept="image/png,image/x-icon,image/svg+xml">
                            <?php if (!empty($settings['favicon'])): ?>
                            <div class="mt-2">
                                <img src="<?= rtrim($app['url'], '/') ?>/<?= e($settings['favicon']) ?>" class="border rounded" style="max-height:32px;" alt="Favicon Preview">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Time Zone</label>
                            <select class="form-select form-select-sm" name="timezone">
                                <?php foreach ($timezoneIdentifiers as $tz): ?>
                                <option value="<?= e($tz) ?>" <?= ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Language</label>
                            <select class="form-select form-select-sm" name="language">
                                <option value="en" <?= ($settings['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save General Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="tab-pane fade <?= $activeTab === 'email' ? 'show active' : '' ?>" id="email" role="tabpanel" aria-labelledby="email-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-envelope me-2"></i>Email Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/settings/email">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">SMTP Host</label>
                            <input type="text" class="form-control form-control-sm" name="mail_host" value="<?= e($settings['mail_host'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-medium text-muted">SMTP Port</label>
                            <input type="number" class="form-control form-control-sm" name="mail_port" value="<?= e($settings['mail_port'] ?? '587') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-medium text-muted">Encryption</label>
                            <select class="form-select form-select-sm" name="mail_encryption">
                                <option value="tls" <?= ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['mail_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['mail_encryption'] ?? 'tls') === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">SMTP Username</label>
                            <input type="text" class="form-control form-control-sm" name="mail_username" value="<?= e($settings['mail_username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">SMTP Password</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" name="mail_password" id="smtpPassword" value="<?= e($settings['mail_password'] ?? '') ?>">
                                <button type="button" class="btn btn-outline-secondary" id="toggleSmtpPassword"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Sender Email</label>
                            <input type="email" class="form-control form-control-sm" name="mail_from_email" value="<?= e($settings['mail_from_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-muted">Sender Name</label>
                            <input type="text" class="form-control form-control-sm" name="mail_from_name" value="<?= e($settings['mail_from_name'] ?? '') ?>">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Email Settings</button>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#testEmailModal"><i class="fas fa-paper-plane me-1"></i>Send Test Email</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="tab-pane fade <?= $activeTab === 'security' ? 'show active' : '' ?>" id="security" role="tabpanel" aria-labelledby="security-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= rtrim($app['url'], '/') ?>/settings/security">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-muted">Session Timeout (minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="session_lifetime" value="<?= e($settings['session_lifetime'] ?? '3600') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-muted">OTP Expiry (minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="otp_expiry" value="<?= e($settings['otp_expiry'] ?? '5') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-muted">Password Expiry (days)</label>
                            <input type="number" class="form-control form-control-sm" name="password_expiry" value="<?= e($settings['password_expiry'] ?? '90') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-muted">Max Login Attempts</label>
                            <input type="number" class="form-control form-control-sm" name="max_login_attempts" value="<?= e($settings['max_login_attempts'] ?? '5') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-muted">Account Lockout Duration (minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="account_lockout_duration" value="<?= e($settings['account_lockout_duration'] ?? '15') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Security Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= rtrim($app['url'], '/') ?>/settings/send-test-email">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="fas fa-paper-plane text-info me-1"></i>Send Test Email</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <label class="form-label small fw-medium text-muted">Recipient Email</label>
                    <input type="email" class="form-control form-control-sm" name="test_email" value="<?= e($settings['mail_from_email'] ?? '') ?>" required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-info text-white"><i class="fas fa-paper-plane me-1"></i>Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('toggleSmtpPassword');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            var input = document.getElementById('smtpPassword');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    }
});
</script>
