<?php

namespace App\Services;

use App\Helpers\Database;

class NotificationService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        return $this->db->insert('notifications', [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function createForUsers(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        foreach ($userIds as $userId) {
            $this->create($userId, $type, $title, $message, $link);
        }
    }

    public function createForRole(string $roleSlug, string $type, string $title, string $message, ?string $link = null): void
    {
        $users = $this->db->fetchAll(
            "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = ? AND u.deleted_at IS NULL",
            [$roleSlug]
        );
        foreach ($users as $user) {
            $this->create($user->id, $type, $title, $message, $link);
        }
    }

    public function sendTaskAssignmentNotification(int $taskId, string $taskNumber, string $title, int $assignedTo, string $assignedByName): void
    {
        $this->create(
            $assignedTo,
            'task_assigned',
            'New Task Assigned',
            "You have been assigned task {$taskNumber}: {$title} by {$assignedByName}",
            "/tasks/view/{$taskId}"
        );

        $emailService = new EmailService();
        $user = $this->db->fetch("SELECT email, first_name, last_name FROM users WHERE id = ?", [$assignedTo]);
        if ($user) {
            $emailService->sendTaskAssignment($user->email, $user->first_name . ' ' . $user->last_name, [
                'task_number' => $taskNumber,
                'title'       => $title,
                'priority'    => '',
                'due_date'    => '',
                'assigned_by' => $assignedByName,
            ]);
        }
    }

    public function sendTaskReassignmentNotification(int $taskId, string $taskNumber, string $title, int $newAssigneeId, string $oldAssigneeName, string $reassignedByName): void
    {
        $this->create(
            $newAssigneeId,
            'task_reassigned',
            'Task Reassigned',
            "Task {$taskNumber}: {$title} has been reassigned to you by {$reassignedByName}",
            "/tasks/view/{$taskId}"
        );

        $newUser = $this->db->fetch("SELECT email, first_name, last_name FROM users WHERE id = ?", [$newAssigneeId]);
        if ($newUser) {
            $emailService = new EmailService();
            $emailService->sendTaskReassignment($newUser->email, $newUser->first_name . ' ' . $newUser->last_name, [
                'task_number' => $taskNumber,
                'title'       => $title,
                'priority'    => '',
                'due_date'    => '',
            ], $oldAssigneeName);
        }
    }

    public function sendTaskCompletedNotification(int $taskId, string $taskNumber, string $title, int $assignedBy, string $completedByName): void
    {
        $this->create(
            $assignedBy,
            'task_completed',
            'Task Completed',
            "Task {$taskNumber}: {$title} has been completed by {$completedByName}",
            "/tasks/view/{$taskId}"
        );
    }

    public function sendMentionNotification(int $mentionedUserId, string $mentionedBy, string $taskNumber, string $comment, int $taskId): void
    {
        $this->create(
            $mentionedUserId,
            'mention',
            'You were mentioned',
            "{$mentionedBy} mentioned you in task {$taskNumber}",
            "/tasks/view/{$taskId}"
        );
    }

    public function getUnreadCount(int $userId): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return $result ? (int)$result->cnt : 0;
    }

    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function getPaginated(int $userId, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->db->fetch("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ?", [$userId]);
        $items = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
        return [
            'items' => $items,
            'total' => (int)$total->cnt,
            'pages' => ceil((int)$total->cnt / $perPage),
        ];
    }

    public function markAsRead(int $notificationId, int $userId): void
    {
        $this->db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }

    public function markAllAsRead(int $userId): void
    {
        $this->db->update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [$userId]);
    }

    public function delete(int $notificationId, int $userId): void
    {
        $this->db->delete('notifications', 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }
}
