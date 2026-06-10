-- ====================================================================
-- Enterprise Task Management System - Database Schema
-- ====================================================================
-- Database: `task_management`
-- Server: MySQL 8.0+
-- Charset: utf8mb4
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `task_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `task_management`;

-- ====================================================================
-- 1. ROLES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_roles_name` (`name`),
    UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 2. DEPARTMENTS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 3. USERS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(20) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(20) NULL,
    `department_id` INT UNSIGNED NULL,
    `role_id` INT UNSIGNED NULL,
    `status` ENUM('Active','Inactive','Suspended','Locked') DEFAULT 'Active',
    `avatar` VARCHAR(255) NULL,
    `password` VARCHAR(255) NOT NULL,
    `failed_attempts` TINYINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_users_employee_id` (`employee_id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_department_id` (`department_id`),
    KEY `idx_users_role_id` (`role_id`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 4. PERMISSIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_permissions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 5. ROLE PERMISSIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permissions_permission_id` (`permission_id`),
    CONSTRAINT `fk_role_permissions_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_role_permissions_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 6. USER PERMISSIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `user_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `granted` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `permission_id`),
    KEY `idx_user_permissions_permission_id` (`permission_id`),
    CONSTRAINT `fk_user_permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permissions_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 7. TASK CATEGORIES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `color` VARCHAR(7) DEFAULT '#2563eb',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 8. TASKS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_number` VARCHAR(20) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category_id` INT UNSIGNED NULL,
    `department_id` INT UNSIGNED NULL,
    `priority` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `assigned_to` INT UNSIGNED NULL,
    `assigned_by` INT UNSIGNED NULL,
    `assigned_at` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('Draft','Open','Assigned','In Progress','Waiting','On Hold','Completed','Cancelled','Overdue') DEFAULT 'Draft',
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `estimated_hours` DECIMAL(8,2) NULL,
    `actual_hours` DECIMAL(8,2) DEFAULT 0.00,
    `progress_percentage` INT DEFAULT 0,
    `attachment` VARCHAR(255) NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_by` INT UNSIGNED NULL,
    `archived` TINYINT(1) DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_tasks_task_number` (`task_number`),
    KEY `idx_tasks_category_id` (`category_id`),
    KEY `idx_tasks_department_id` (`department_id`),
    KEY `idx_tasks_assigned_to` (`assigned_to`),
    KEY `idx_tasks_assigned_by` (`assigned_by`),
    KEY `idx_tasks_status` (`status`),
    KEY `idx_tasks_priority` (`priority`),
    KEY `idx_tasks_due_date` (`due_date`),
    CONSTRAINT `fk_tasks_category_id` FOREIGN KEY (`category_id`) REFERENCES `task_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tasks_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tasks_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 9. TASK COMMENTS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `is_internal` TINYINT(1) DEFAULT 0,
    `attachment` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_task_comments_task_id` (`task_id`),
    KEY `idx_task_comments_user_id` (`user_id`),
    CONSTRAINT `fk_task_comments_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_task_comments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 10. TASK ATTACHMENTS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `file_size` INT UNSIGNED NULL,
    `file_path` VARCHAR(500) NULL,
    `extension` VARCHAR(20) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_task_attachments_task_id` (`task_id`),
    KEY `idx_task_attachments_user_id` (`user_id`),
    CONSTRAINT `fk_task_attachments_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_task_attachments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 11. TASK HISTORY
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `field_changed` VARCHAR(50) NOT NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_task_history_task_id` (`task_id`),
    KEY `idx_task_history_user_id` (`user_id`),
    CONSTRAINT `fk_task_history_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_task_history_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 12. NOTIFICATIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_is_read` (`is_read`),
    CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 13. NOTIFICATION PREFERENCES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `email` TINYINT(1) DEFAULT 1,
    `in_app` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_notification_preferences_user_id` (`user_id`),
    CONSTRAINT `fk_notification_preferences_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 14. ACTIVITY LOGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `record_id` INT UNSIGNED NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `device` VARCHAR(20) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_activity_logs_user_id` (`user_id`),
    KEY `idx_activity_logs_action` (`action`),
    KEY `idx_activity_logs_module` (`module`),
    KEY `idx_activity_logs_record_id` (`record_id`),
    CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 15. LOGIN LOGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(50) NOT NULL,
    `status` ENUM('success','failed') NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_login_logs_user_id` (`user_id`),
    KEY `idx_login_logs_status` (`status`),
    CONSTRAINT `fk_login_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 16. PASSWORD RESETS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_password_resets_user_id` (`user_id`),
    KEY `idx_password_resets_token` (`token`(191)),
    CONSTRAINT `fk_password_resets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 17. OTP CODES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `otp` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `attempts` TINYINT DEFAULT 0,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_otp_codes_user_id` (`user_id`),
    KEY `idx_otp_codes_email` (`email`),
    CONSTRAINT `fk_otp_codes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 18. REMEMBER TOKENS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_remember_tokens_user_id` (`user_id`),
    KEY `idx_remember_tokens_token_hash` (`token_hash`(191)),
    CONSTRAINT `fk_remember_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 19. BACKUP HISTORY
-- ====================================================================
CREATE TABLE IF NOT EXISTS `backup_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size` BIGINT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `type` ENUM('manual','scheduled') NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `status` ENUM('success','failed') DEFAULT 'success',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_backup_history_created_by` (`created_by`),
    CONSTRAINT `fk_backup_history_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 20. EMAIL TEMPLATES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `template_type` VARCHAR(50) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_email_templates_template_type` (`template_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 21. EMAIL LOGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recipient` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('sent','failed') NOT NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_email_logs_recipient` (`recipient`),
    KEY `idx_email_logs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 22. SYSTEM SETTINGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- ----------------------------------------------------------------
-- 1. Default Departments
-- ----------------------------------------------------------------
INSERT INTO `departments` (`name`, `description`) VALUES
('Administration', 'Handles administrative and operational tasks'),
('Development', 'Software development and engineering team'),
('Design', 'UI/UX and graphic design team'),
('Marketing', 'Marketing and brand management'),
('Sales', 'Sales and business development'),
('Human Resources', 'HR and personnel management'),
('Finance', 'Financial management and accounting');

-- ----------------------------------------------------------------
-- 2. Default Roles
-- ----------------------------------------------------------------
INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
('Administrator', 'administrator', 'Full system access and control'),
('Manager', 'manager', 'Department management and oversight'),
('Supervisor', 'supervisor', 'Team supervision and task management'),
('Staff', 'staff', 'Regular staff member');

-- ----------------------------------------------------------------
-- 3. Default Permissions
-- Modules: users, roles, permissions, tasks, categories, departments,
--          reports, activity_logs, notifications, backup, settings
-- Actions: view, add, edit, delete, assign, approve, export, print
-- ----------------------------------------------------------------
INSERT INTO `permissions` (`name`, `slug`, `module`) VALUES
-- Users
('View Users', 'users.view', 'users'),
('Add Users', 'users.add', 'users'),
('Edit Users', 'users.edit', 'users'),
('Delete Users', 'users.delete', 'users'),
('Assign Users', 'users.assign', 'users'),
('Approve Users', 'users.approve', 'users'),
('Export Users', 'users.export', 'users'),
('Print Users', 'users.print', 'users'),
-- Roles
('View Roles', 'roles.view', 'roles'),
('Add Roles', 'roles.add', 'roles'),
('Edit Roles', 'roles.edit', 'roles'),
('Delete Roles', 'roles.delete', 'roles'),
('Assign Roles', 'roles.assign', 'roles'),
('Approve Roles', 'roles.approve', 'roles'),
('Export Roles', 'roles.export', 'roles'),
('Print Roles', 'roles.print', 'roles'),
-- Permissions
('View Permissions', 'permissions.view', 'permissions'),
('Add Permissions', 'permissions.add', 'permissions'),
('Edit Permissions', 'permissions.edit', 'permissions'),
('Delete Permissions', 'permissions.delete', 'permissions'),
('Assign Permissions', 'permissions.assign', 'permissions'),
('Approve Permissions', 'permissions.approve', 'permissions'),
('Export Permissions', 'permissions.export', 'permissions'),
('Print Permissions', 'permissions.print', 'permissions'),
-- Tasks
('View Tasks', 'tasks.view', 'tasks'),
('Add Tasks', 'tasks.add', 'tasks'),
('Edit Tasks', 'tasks.edit', 'tasks'),
('Delete Tasks', 'tasks.delete', 'tasks'),
('Assign Tasks', 'tasks.assign', 'tasks'),
('Approve Tasks', 'tasks.approve', 'tasks'),
('Export Tasks', 'tasks.export', 'tasks'),
('Print Tasks', 'tasks.print', 'tasks'),
-- Categories
('View Categories', 'categories.view', 'categories'),
('Add Categories', 'categories.add', 'categories'),
('Edit Categories', 'categories.edit', 'categories'),
('Delete Categories', 'categories.delete', 'categories'),
('Assign Categories', 'categories.assign', 'categories'),
('Approve Categories', 'categories.approve', 'categories'),
('Export Categories', 'categories.export', 'categories'),
('Print Categories', 'categories.print', 'categories'),
-- Departments
('View Departments', 'departments.view', 'departments'),
('Add Departments', 'departments.add', 'departments'),
('Edit Departments', 'departments.edit', 'departments'),
('Delete Departments', 'departments.delete', 'departments'),
('Assign Departments', 'departments.assign', 'departments'),
('Approve Departments', 'departments.approve', 'departments'),
('Export Departments', 'departments.export', 'departments'),
('Print Departments', 'departments.print', 'departments'),
-- Reports
('View Reports', 'reports.view', 'reports'),
('Add Reports', 'reports.add', 'reports'),
('Edit Reports', 'reports.edit', 'reports'),
('Delete Reports', 'reports.delete', 'reports'),
('Assign Reports', 'reports.assign', 'reports'),
('Approve Reports', 'reports.approve', 'reports'),
('Export Reports', 'reports.export', 'reports'),
('Print Reports', 'reports.print', 'reports'),
-- Activity Logs
('View Activity Logs', 'activity_logs.view', 'activity_logs'),
('Add Activity Logs', 'activity_logs.add', 'activity_logs'),
('Edit Activity Logs', 'activity_logs.edit', 'activity_logs'),
('Delete Activity Logs', 'activity_logs.delete', 'activity_logs'),
('Assign Activity Logs', 'activity_logs.assign', 'activity_logs'),
('Approve Activity Logs', 'activity_logs.approve', 'activity_logs'),
('Export Activity Logs', 'activity_logs.export', 'activity_logs'),
('Print Activity Logs', 'activity_logs.print', 'activity_logs'),
-- Notifications
('View Notifications', 'notifications.view', 'notifications'),
('Add Notifications', 'notifications.add', 'notifications'),
('Edit Notifications', 'notifications.edit', 'notifications'),
('Delete Notifications', 'notifications.delete', 'notifications'),
('Assign Notifications', 'notifications.assign', 'notifications'),
('Approve Notifications', 'notifications.approve', 'notifications'),
('Export Notifications', 'notifications.export', 'notifications'),
('Print Notifications', 'notifications.print', 'notifications'),
-- Backup
('View Backup', 'backup.view', 'backup'),
('Add Backup', 'backup.add', 'backup'),
('Edit Backup', 'backup.edit', 'backup'),
('Delete Backup', 'backup.delete', 'backup'),
('Assign Backup', 'backup.assign', 'backup'),
('Approve Backup', 'backup.approve', 'backup'),
('Export Backup', 'backup.export', 'backup'),
('Print Backup', 'backup.print', 'backup'),
-- Settings
('View Settings', 'settings.view', 'settings'),
('Add Settings', 'settings.add', 'settings'),
('Edit Settings', 'settings.edit', 'settings'),
('Delete Settings', 'settings.delete', 'settings'),
('Assign Settings', 'settings.assign', 'settings'),
('Approve Settings', 'settings.approve', 'settings'),
('Export Settings', 'settings.export', 'settings'),
('Print Settings', 'settings.print', 'settings');

-- ----------------------------------------------------------------
-- 4. Grant ALL Permissions to Administrator Role
-- ----------------------------------------------------------------
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'administrator';

-- ----------------------------------------------------------------
-- 5. Default Admin User
-- Password: 'password' (bcrypt hash)
-- Employee ID: ADMIN-001
-- ----------------------------------------------------------------
INSERT INTO `users` (`employee_id`, `first_name`, `last_name`, `username`, `email`, `mobile`, `department_id`, `role_id`, `status`, `password`)
SELECT
    'ADMIN-001',
    'System',
    'Administrator',
    'admin',
    'admin@taskms.com',
    NULL,
    (SELECT `id` FROM `departments` WHERE `name` = 'Administration' LIMIT 1),
    (SELECT `id` FROM `roles` WHERE `slug` = 'administrator' LIMIT 1),
    'Active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ----------------------------------------------------------------
-- 6. Default System Settings
-- ----------------------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('session_timeout', '1800'),
('otp_expiry', '600'),
('password_expiry', '7776000'),
('max_login_attempts', '5'),
('lockout_duration', '1800');

-- ----------------------------------------------------------------
-- 7. Default Email Templates
-- ----------------------------------------------------------------
-- ====================================================================
-- 23. MEETINGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `meetings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `department_id` INT UNSIGNED NULL,
    `organizer_id` INT UNSIGNED NOT NULL,
    `status` ENUM('Scheduled','Ongoing','Completed','Cancelled') DEFAULT 'Scheduled',
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `location` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    KEY `idx_meetings_department_id` (`department_id`),
    KEY `idx_meetings_organizer_id` (`organizer_id`),
    KEY `idx_meetings_status` (`status`),
    CONSTRAINT `fk_meetings_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_meetings_organizer_id` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 24. MEETING SESSIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `meeting_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `presenter_id` INT UNSIGNED NULL,
    `duration_minutes` INT UNSIGNED NULL,
    `sort_order` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_meeting_sessions_meeting_id` (`meeting_id`),
    KEY `idx_meeting_sessions_presenter_id` (`presenter_id`),
    CONSTRAINT `fk_ms_meeting_id` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ms_presenter_id` FOREIGN KEY (`presenter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 25. MEETING TASKS (tasks created from meetings)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `meeting_tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `assigned_to` INT UNSIGNED NULL,
    `due_date` DATE NULL,
    `status` ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_meeting_tasks_meeting_id` (`meeting_id`),
    KEY `idx_meeting_tasks_task_id` (`task_id`),
    KEY `idx_meeting_tasks_assigned_to` (`assigned_to`),
    CONSTRAINT `fk_mt_meeting_id` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mt_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_mt_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 26. SPECIAL MEETINGS (requests that need approval)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `special_meetings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `requester_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NULL,
    `preferred_date` DATETIME NULL,
    `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `rejection_reason` TEXT NULL,
    `meeting_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_special_meetings_requester_id` (`requester_id`),
    KEY `idx_special_meetings_approved_by` (`approved_by`),
    KEY `idx_special_meetings_status` (`status`),
    CONSTRAINT `fk_sm_requester_id` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sm_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sm_meeting_id` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` (`name`, `template_type`, `subject`, `body`) VALUES
('Password Reset OTP', 'password_reset_otp', 'Your Password Reset OTP Code',
 '<!DOCTYPE html><html><body style=\"font-family: Arial, sans-serif; padding: 20px;\">'
 '<h2>Password Reset Request</h2>'
 '<p>Dear {full_name},</p>'
 '<p>You have requested to reset your password. Use the OTP code below to proceed:</p>'
 '<div style=\"text-align: center; margin: 30px 0;\">'
 '<span style=\"font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #2563eb;\">{otp_code}</span></div>'
 '<p>This OTP will expire in {otp_expiry_minutes} minutes.</p>'
 '<p>If you did not request this, please ignore this email.</p>'
 '<br><p>Best regards,<br>Task Management System</p></body></html>'),
('Task Assignment Notification', 'task_assigned', 'New Task Assigned: {task_title}',
 '<!DOCTYPE html><html><body style=\"font-family: Arial, sans-serif; padding: 20px;\">'
 '<h2>Task Assignment</h2>'
 '<p>Dear {assigned_to_name},</p>'
 '<p>You have been assigned a new task by <strong>{assigned_by_name}</strong>.</p>'
 '<table style=\"border-collapse: collapse; width: 100%; max-width: 500px; margin: 20px 0;\">'
 '<tr><td style=\"padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;\">Task:</td>'
 '<td style=\"padding: 8px; border-bottom: 1px solid #ddd;\">{task_title}</td></tr>'
 '<tr><td style=\"padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;\">Priority:</td>'
 '<td style=\"padding: 8px; border-bottom: 1px solid #ddd;\">{priority}</td></tr>'
 '<tr><td style=\"padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;\">Due Date:</td>'
 '<td style=\"padding: 8px; border-bottom: 1px solid #ddd;\">{due_date}</td></tr>'
 '<tr><td style=\"padding: 8px; font-weight: bold;\">Description:</td>'
 '<td style=\"padding: 8px;\">{task_description}</td></tr></table>'
 '<p>Please log in to the system to view details.</p>'
 '<br><p>Best regards,<br>Task Management System</p></body></html>');
