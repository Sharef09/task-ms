<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class MeetingTask
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByMeeting(int $meetingId): array
    {
        try {
            $sql = "SELECT mt.*, u.first_name, u.last_name, t.task_number
                    FROM meeting_tasks mt
                    LEFT JOIN users u ON u.id = mt.assigned_to
                    LEFT JOIN tasks t ON t.id = mt.task_id
                    WHERE mt.meeting_id = :meeting_id
                    ORDER BY mt.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':meeting_id', $meetingId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('MeetingTask::getByMeeting - ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('meeting_tasks', $data);
        } catch (RuntimeException $e) {
            error_log('MeetingTask::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('meeting_tasks', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('MeetingTask::update - ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $db = Database::getInstance();
            $db->delete('meeting_tasks', 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('MeetingTask::delete - ' . $e->getMessage());
        }
    }
}
