<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Notification
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get paginated notifications for a user.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getByUser(int $userId, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT * FROM notifications
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Notification::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new notification.
     *
     * @param array $data
     * @return int The inserted notification ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('notifications', $data);
        } catch (RuntimeException $e) {
            error_log('Notification::create - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a single notification as read.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $id, int $userId): bool
    {
        try {
            $db = Database::getInstance();
            $db->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = ? AND user_id = ?', [
                $id,
                $userId,
            ]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Notification::markAsRead - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW()
                    WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (RuntimeException $e) {
            error_log('Notification::markAllAsRead - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a notification for a user.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete(int $id, int $userId): bool
    {
        try {
            $db = Database::getInstance();
            $db->delete('notifications', 'id = :id AND user_id = :user_id', [
                'id'      => $id,
                'user_id' => $userId,
            ]);
            return true;
        } catch (RuntimeException $e) {
            error_log('Notification::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count for a user.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        } catch (RuntimeException $e) {
            error_log('Notification::getUnreadCount - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent notifications for a user.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecent(int $userId, int $limit): array
    {
        try {
            $sql = "SELECT * FROM notifications
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :lim";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Notification::getRecent - ' . $e->getMessage());
            return [];
        }
    }
}
