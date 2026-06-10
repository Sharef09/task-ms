<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class SpecialMeeting
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $where = ['1=1'];
            $params = [];
            if (!empty($filters['status'])) {
                $where[] = 'sm.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['requester_id'])) {
                $where[] = 'sm.requester_id = :requester_id';
                $params['requester_id'] = $filters['requester_id'];
            }
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT sm.*, req.first_name AS requester_first_name, req.last_name AS requester_last_name,
                           app.first_name AS approver_first_name, app.last_name AS approver_last_name,
                           d.name AS department_name
                    FROM special_meetings sm
                    LEFT JOIN users req ON req.id = sm.requester_id
                    LEFT JOIN users app ON app.id = sm.approved_by
                    LEFT JOIN departments d ON d.id = sm.department_id
                    {$whereClause}
                    ORDER BY sm.created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('SpecialMeeting::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT sm.*, req.first_name AS requester_first_name, req.last_name AS requester_last_name,
                           app.first_name AS approver_first_name, app.last_name AS approver_last_name,
                           d.name AS department_name
                    FROM special_meetings sm
                    LEFT JOIN users req ON req.id = sm.requester_id
                    LEFT JOIN users app ON app.id = sm.approved_by
                    LEFT JOIN departments d ON d.id = sm.department_id
                    WHERE sm.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('SpecialMeeting::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('special_meetings', $data);
        } catch (RuntimeException $e) {
            error_log('SpecialMeeting::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('special_meetings', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('SpecialMeeting::update - ' . $e->getMessage());
        }
    }

    public function countPending(): int
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM special_meetings WHERE status = 'Pending'");
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (RuntimeException $e) {
            return 0;
        }
    }
}
