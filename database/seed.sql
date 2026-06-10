-- ============================================================
-- Seed Data: 3 rows per table
-- ============================================================
USE `task_management`;

SET FOREIGN_KEY_CHECKS = 0;

-- Skip roles (already seeded), add sample roles if missing
INSERT IGNORE INTO `roles` (`name`, `slug`, `description`) VALUES
('Manager', 'manager', 'Manager role with elevated permissions'),
('Supervisor', 'supervisor', 'Supervisor role with team oversight'),
('Staff', 'staff', 'Standard staff member');

-- Skip departments (already seeded), add more
INSERT IGNORE INTO `departments` (`name`, `description`) VALUES
('Engineering', 'Software and systems engineering'),
('Quality Assurance', 'Testing and quality control'),
('Customer Support', 'Customer service and support');

-- Skip permissions (already seeded in schema.sql)

-- Sample users (if not already present)
INSERT IGNORE INTO `users` (`employee_id`, `first_name`, `last_name`, `username`, `email`, `mobile`, `department_id`, `role_id`, `status`, `password`)
SELECT 'EMP-0001', 'John', 'Doe', 'john', 'john@taskms.com', '555-0101', (SELECT id FROM departments WHERE name = 'Development' LIMIT 1), (SELECT id FROM roles WHERE slug = 'staff' LIMIT 1), 'Active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'john');

INSERT IGNORE INTO `users` (`employee_id`, `first_name`, `last_name`, `username`, `email`, `mobile`, `department_id`, `role_id`, `status`, `password`)
SELECT 'EMP-0002', 'Jane', 'Smith', 'jane', 'jane@taskms.com', '555-0102', (SELECT id FROM departments WHERE name = 'Engineering' LIMIT 1), (SELECT id FROM roles WHERE slug = 'manager' LIMIT 1), 'Active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'jane');

INSERT IGNORE INTO `users` (`employee_id`, `first_name`, `last_name`, `username`, `email`, `mobile`, `department_id`, `role_id`, `status`, `password`)
SELECT 'EMP-0003', 'Bob', 'Wilson', 'bob', 'bob@taskms.com', '555-0103', (SELECT id FROM departments WHERE name = 'Quality Assurance' LIMIT 1), (SELECT id FROM roles WHERE slug = 'supervisor' LIMIT 1), 'Active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'bob');

-- Task categories
INSERT IGNORE INTO `task_categories` (`name`, `description`, `color`) VALUES
('Development', 'Software development tasks', '#2563eb'),
('Design', 'UI/UX design tasks', '#d97706'),
('Testing', 'Testing and QA tasks', '#16a34a');

-- Sample tasks
INSERT INTO `tasks` (`task_number`, `title`, `description`, `category_id`, `department_id`, `priority`, `assigned_to`, `assigned_by`, `status`, `start_date`, `due_date`, `estimated_hours`, `progress_percentage`, `created_at`, `updated_at`)
SELECT 'TASK-00001', 'User Dashboard Redesign', 'Redesign the user dashboard with new charts and widgets', (SELECT id FROM task_categories WHERE name = 'Design' LIMIT 1), (SELECT id FROM departments WHERE name = 'Development' LIMIT 1), 'High', (SELECT id FROM users WHERE username = 'john' LIMIT 1), (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 'In Progress', '2026-06-01', '2026-06-20', 40, 60, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE task_number = 'TASK-00001');

INSERT INTO `tasks` (`task_number`, `title`, `description`, `category_id`, `department_id`, `priority`, `assigned_to`, `assigned_by`, `status`, `start_date`, `due_date`, `estimated_hours`, `progress_percentage`, `created_at`, `updated_at`)
SELECT 'TASK-00002', 'API Integration Module', 'Build REST API integration for third-party services', (SELECT id FROM task_categories WHERE name = 'Development' LIMIT 1), (SELECT id FROM departments WHERE name = 'Engineering' LIMIT 1), 'Critical', (SELECT id FROM users WHERE username = 'jane' LIMIT 1), (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 'Assigned', '2026-06-05', '2026-06-25', 60, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE task_number = 'TASK-00002');

INSERT INTO `tasks` (`task_number`, `title`, `description`, `category_id`, `department_id`, `priority`, `assigned_to`, `assigned_by`, `status`, `start_date`, `due_date`, `estimated_hours`, `progress_percentage`, `created_at`, `updated_at`)
SELECT 'TASK-00003', 'Database Optimization', 'Optimize slow queries and add missing indexes', (SELECT id FROM task_categories WHERE name = 'Testing' LIMIT 1), (SELECT id FROM departments WHERE name = 'Quality Assurance' LIMIT 1), 'Medium', (SELECT id FROM users WHERE username = 'bob' LIMIT 1), (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 'Completed', '2026-05-20', '2026-06-10', 25, 100, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE task_number = 'TASK-00003');

-- Task comments
INSERT INTO `task_comments` (`task_id`, `user_id`, `comment`, `is_internal`, `created_at`)
SELECT t.id, u.id, 'Started working on the wireframes. Will share the mockups soon.', 0, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00001' AND u.username = 'john'
AND NOT EXISTS (SELECT 1 FROM task_comments WHERE task_id = t.id AND user_id = u.id);

INSERT INTO `task_comments` (`task_id`, `user_id`, `comment`, `is_internal`, `created_at`)
SELECT t.id, u.id, 'Please focus on mobile-first approach.', 1, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00001' AND u.username = 'admin'
AND NOT EXISTS (SELECT 1 FROM task_comments WHERE task_id = t.id AND user_id = u.id AND is_internal = 1);

INSERT INTO `task_comments` (`task_id`, `user_id`, `comment`, `is_internal`, `created_at`)
SELECT t.id, u.id, 'API documentation is ready for review.', 0, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00002' AND u.username = 'jane'
AND NOT EXISTS (SELECT 1 FROM task_comments WHERE task_id = t.id AND user_id = u.id);

-- Task attachments
INSERT INTO `task_attachments` (`task_id`, `user_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at`)
SELECT t.id, u.id, 'mockup-v1.png', CONCAT('mockup-', UUID()), 'image/png', 204800, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00001' AND u.username = 'john'
AND NOT EXISTS (SELECT 1 FROM task_attachments WHERE task_id = t.id AND original_name = 'mockup-v1.png');

INSERT INTO `task_attachments` (`task_id`, `user_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at`)
SELECT t.id, u.id, 'api-specs.pdf', CONCAT('api-specs-', UUID()), 'application/pdf', 512000, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00002' AND u.username = 'jane'
AND NOT EXISTS (SELECT 1 FROM task_attachments WHERE task_id = t.id AND original_name = 'api-specs.pdf');

INSERT INTO `task_attachments` (`task_id`, `user_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at`)
SELECT t.id, u.id, 'query-report.xlsx', CONCAT('query-report-', UUID()), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1024000, NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00003' AND u.username = 'bob'
AND NOT EXISTS (SELECT 1 FROM task_attachments WHERE task_id = t.id AND original_name = 'query-report.xlsx');

-- Task history
INSERT INTO `task_history` (`task_id`, `user_id`, `field_changed`, `old_value`, `new_value`, `created_at`)
SELECT t.id, u.id, 'status', 'Draft', 'Assigned', NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00001' AND u.username = 'admin'
AND NOT EXISTS (SELECT 1 FROM task_history WHERE task_id = t.id AND field_changed = 'status' AND old_value = 'Draft');

INSERT INTO `task_history` (`task_id`, `user_id`, `field_changed`, `old_value`, `new_value`, `created_at`)
SELECT t.id, u.id, 'status', 'Assigned', 'In Progress', NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00001' AND u.username = 'john'
AND NOT EXISTS (SELECT 1 FROM task_history WHERE task_id = t.id AND field_changed = 'status' AND old_value = 'Assigned');

INSERT INTO `task_history` (`task_id`, `user_id`, `field_changed`, `old_value`, `new_value`, `created_at`)
SELECT t.id, u.id, 'status', 'Assigned', 'Completed', NOW()
FROM tasks t, users u WHERE t.task_number = 'TASK-00003' AND u.username = 'bob'
AND NOT EXISTS (SELECT 1 FROM task_history WHERE task_id = t.id AND field_changed = 'status' AND old_value = 'Completed');

-- Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`)
SELECT u.id, 'task_assigned', 'New Task Assigned', 'You have been assigned TASK-00001: User Dashboard Redesign', '/tasks/view/1', 0, NOW()
FROM users u WHERE u.username = 'john'
AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = u.id AND type = 'task_assigned');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`)
SELECT u.id, 'task_assigned', 'New Task Assigned', 'You have been assigned TASK-00002: API Integration Module', '/tasks/view/2', 0, NOW()
FROM users u WHERE u.username = 'jane'
AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = u.id AND type = 'task_assigned' AND title LIKE '%TASK-00002%');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`)
SELECT u.id, 'task_completed', 'Task Completed', 'TASK-00003: Database Optimization has been completed', '/tasks/view/3', 0, NOW()
FROM users u WHERE u.username = 'admin'
AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = u.id AND type = 'task_completed');

-- Notification preferences
INSERT IGNORE INTO `notification_preferences` (`user_id`, `type`, `email`, `in_app`)
SELECT u.id, 'task_assigned', 1, 1 FROM users u;
INSERT IGNORE INTO `notification_preferences` (`user_id`, `type`, `email`, `in_app`)
SELECT u.id, 'task_completed', 1, 1 FROM users u;
INSERT IGNORE INTO `notification_preferences` (`user_id`, `type`, `email`, `in_app`)
SELECT u.id, 'task_overdue', 1, 1 FROM users u;

-- Login logs
INSERT INTO `login_logs` (`user_id`, `username`, `status`, `ip_address`, `user_agent`, `created_at`)
VALUES (1, 'admin', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 1 HOUR));
INSERT INTO `login_logs` (`user_id`, `username`, `status`, `ip_address`, `user_agent`, `created_at`)
VALUES (2, 'john', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 2 HOUR));
INSERT INTO `login_logs` (`user_id`, `username`, `status`, `ip_address`, `user_agent`, `created_at`)
VALUES (3, 'jane', 'success', '192.168.1.100', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Activity logs
INSERT INTO `activity_logs` (`user_id`, `action`, `module`, `record_id`, `ip_address`, `user_agent`, `device`, `created_at`)
VALUES (1, 'Login', 'Auth', 1, '127.0.0.1', 'Mozilla/5.0', 'Desktop', DATE_SUB(NOW(), INTERVAL 1 HOUR));
INSERT INTO `activity_logs` (`user_id`, `action`, `module`, `record_id`, `ip_address`, `user_agent`, `device`, `created_at`)
VALUES (1, 'Create', 'Tasks', 1, '127.0.0.1', 'Mozilla/5.0', 'Desktop', DATE_SUB(NOW(), INTERVAL 2 HOUR));
INSERT INTO `activity_logs` (`user_id`, `action`, `module`, `record_id`, `ip_address`, `user_agent`, `device`, `created_at`)
VALUES (2, 'Update', 'Tasks', 1, '127.0.0.1', 'Mozilla/5.0', 'Desktop', DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- System settings (ensure they exist)
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Task Management System'),
('company_name', 'Enterprise Inc.'),
('timezone', 'UTC'),
('language', 'en');

SET FOREIGN_KEY_CHECKS = 1;
