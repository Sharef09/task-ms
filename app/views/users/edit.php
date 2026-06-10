<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
    <a href="<?= rtrim($app['url'], '/') ?>/users" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= rtrim($app['url'], '/') ?>/users/update/<?= e($user->id) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label small fw-medium">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" value="<?= e(old('first_name', $user->first_name)) ?>" required>
                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" value="<?= e(old('last_name', $user->last_name)) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label small fw-medium">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-at"></i></span>
                        <input type="text" class="form-control" id="username" value="<?= e($user->username) ?>" disabled>
                        <span class="input-group-text text-muted small" title="Username cannot be changed"><i class="fas fa-lock"></i></span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label small fw-medium">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" value="<?= e(old('email', $user->email)) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-medium">Mobile</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number" value="<?= e(old('mobile', $user->mobile ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="department_id" class="form-label small fw-medium">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= e($dept->id) ?>" <?= (old('department_id', $user->department_id) == $dept->id) ? 'selected' : '' ?>><?= e($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="role_id" class="form-label small fw-medium">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role->id) ?>" <?= (old('role_id', $user->role_id) == $role->id) ? 'selected' : '' ?>><?= e($role->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label small fw-medium">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="Active" <?= (old('status', $user->status) === 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= (old('status', $user->status) === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="Suspended" <?= (old('status', $user->status) === 'Suspended') ? 'selected' : '' ?>>Suspended</option>
                        <option value="Locked" <?= (old('status', $user->status) === 'Locked') ? 'selected' : '' ?>>Locked</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label small fw-medium">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
                    </div>
                    <div class="form-text small text-muted">Leave blank to keep current password.</div>
                </div>

                <div class="col-md-6">
                    <label for="profile_photo" class="form-label small fw-medium">Profile Photo</label>
                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                    <div class="form-text small">Accepted: JPG, PNG, GIF. Max 2MB.</div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update User</button>
                <a href="<?= rtrim($app['url'], '/') ?>/users" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
