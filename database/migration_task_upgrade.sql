-- ====================================================================
-- Task Management Module Upgrade
-- Adds enterprise workflow features while preserving existing data
-- ====================================================================

-- Alter tasks table: add new columns
ALTER TABLE `tasks`
  ADD COLUMN `estimated_completion_date` DATE NULL AFTER `due_date`,
  ADD COLUMN `recurring_type` ENUM('none','daily','weekly','monthly','yearly') DEFAULT 'none' AFTER `archived`,
  ADD COLUMN `recurring_interval` INT UNSIGNED DEFAULT 1 AFTER `recurring_type`,
  ADD COLUMN `recurring_end_date` DATE NULL AFTER `recurring_interval`,
  ADD COLUMN `template_id` INT UNSIGNED NULL AFTER `recurring_end_date`,
  ADD COLUMN `escalated` TINYINT(1) DEFAULT 0 AFTER `template_id`,
  ADD COLUMN `disputed` TINYINT(1) DEFAULT 0 AFTER `escalated`,
  ADD COLUMN `rejection_reason` TEXT NULL AFTER `disputed`,
  ADD COLUMN `completed_notes` TEXT NULL AFTER `rejection_reason`,
  ADD INDEX `idx_tasks_recurring_type` (`recurring_type`),
  ADD INDEX `idx_tasks_escalated` (`escalated`),
  ADD INDEX `idx_tasks_disputed` (`disputed`);

-- Update status ENUM to include new workflow statuses
ALTER TABLE `tasks` MODIFY COLUMN `status` ENUM('Draft','Pending','In Progress','Under Review','Completed','Rejected','Cancelled','Overdue') DEFAULT 'Draft';

-- ====================================================================
-- 27. TASK ASSIGNMENTS (supports multiple assignees)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `assigned_by` INT UNSIGNED NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_primary` TINYINT(1) DEFAULT 0,
    UNIQUE KEY `uk_task_user` (`task_id`, `user_id`),
    KEY `idx_ta_task_id` (`task_id`),
    KEY `idx_ta_user_id` (`user_id`),
    CONSTRAINT `fk_ta_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ta_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 28. TASK DEPENDENCIES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_dependencies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `depends_on_id` INT UNSIGNED NOT NULL,
    `dependency_type` ENUM('blocks','blocked_by','related') DEFAULT 'blocked_by',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_task_dep` (`task_id`, `depends_on_id`),
    KEY `idx_td_task_id` (`task_id`),
    KEY `idx_td_depends_on_id` (`depends_on_id`),
    CONSTRAINT `fk_td_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_td_depends_on_id` FOREIGN KEY (`depends_on_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 29. TASK TEMPLATES
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category_id` INT UNSIGNED NULL,
    `department_id` INT UNSIGNED NULL,
    `priority` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `estimated_hours` DECIMAL(8,2) NULL,
    `template_data` JSON NULL,
    `is_public` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_tt_category_id` (`category_id`),
    KEY `idx_tt_department_id` (`department_id`),
    KEY `idx_tt_created_by` (`created_by`),
    CONSTRAINT `fk_tt_category_id` FOREIGN KEY (`category_id`) REFERENCES `task_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tt_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tt_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 30. TASK TAGS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `tag` VARCHAR(50) NOT NULL,
    KEY `idx_ttag_task_id` (`task_id`),
    KEY `idx_ttag_tag` (`tag`),
    CONSTRAINT `fk_ttag_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 31. TASK WATCHERS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_watchers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tw_task_user` (`task_id`, `user_id`),
    KEY `idx_tw_task_id` (`task_id`),
    KEY `idx_tw_user_id` (`user_id`),
    CONSTRAINT `fk_tw_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tw_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 32. TASK REMINDERS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_reminders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `remind_at` DATETIME NOT NULL,
    `reminder_type` ENUM('due_date','custom') DEFAULT 'due_date',
    `sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tr_task_id` (`task_id`),
    KEY `idx_tr_user_id` (`user_id`),
    KEY `idx_tr_remind_at` (`remind_at`),
    CONSTRAINT `fk_tr_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tr_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 33. EMPLOYEE IDEAS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `employee_ideas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category` ENUM('Improvement','Innovation','Efficiency','Cost Saving','Safety','Other') DEFAULT 'Improvement',
    `status` ENUM('Submitted','Under Review','Approved','Implemented','Rejected') DEFAULT 'Submitted',
    `submitted_by` INT UNSIGNED NOT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `review_notes` TEXT NULL,
    `estimated_savings` DECIMAL(12,2) NULL,
    `attachment` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    KEY `idx_ei_submitted_by` (`submitted_by`),
    KEY `idx_ei_status` (`status`),
    KEY `idx_ei_category` (`category`),
    CONSTRAINT `fk_ei_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ei_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 34. USER FILES (My Files repository)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `user_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `file_size` INT UNSIGNED NULL,
    `folder` VARCHAR(100) DEFAULT '/',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_uf_user_id` (`user_id`),
    KEY `idx_uf_task_id` (`task_id`),
    KEY `idx_uf_folder` (`folder`),
    CONSTRAINT `fk_uf_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_uf_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 35. TASK MENTIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS `task_mentions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `comment_id` INT UNSIGNED NULL,
    `mentioned_user_id` INT UNSIGNED NOT NULL,
    `mentioned_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tm_task_id` (`task_id`),
    KEY `idx_tm_mentioned_user_id` (`mentioned_user_id`),
    CONSTRAINT `fk_tm_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tm_mentioned_user_id` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
