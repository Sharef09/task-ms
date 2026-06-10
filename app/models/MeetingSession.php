<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class MeetingSession
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getByMeeting(int $meetingId): array
    {
        try {
            $sql = "SELECT ms.*, u.first_name, u.last_name
                    FROM meeting_sessions ms
                    LEFT JOIN users u ON u.id = ms.presenter_id
                    WHERE ms.meeting_id = :meeting_id
                    ORDER BY ms.sort_order ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':meeting_id', $meetingId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('MeetingSession::getByMeeting - ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('meeting_sessions', $data);
        } catch (RuntimeException $e) {
            error_log('MeetingSession::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('meeting_sessions', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('MeetingSession::update - ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $db = Database::getInstance();
            $db->delete('meeting_sessions', 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('MeetingSession::delete - ' . $e->getMessage());
        }
    }
}
