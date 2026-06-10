<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class Meeting
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $where = ['m.deleted_at IS NULL'];
            $params = [];
            if (!empty($filters['search'])) {
                $where[] = '(m.title LIKE :search OR m.description LIKE :search2)';
                $params['search'] = "%{$filters['search']}%";
                $params['search2'] = "%{$filters['search']}%";
            }
            if (!empty($filters['status'])) {
                $where[] = 'm.status = :status';
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['department_id'])) {
                $where[] = 'm.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT m.*, org.first_name AS organizer_first_name, org.last_name AS organizer_last_name,
                           d.name AS department_name
                    FROM meetings m
                    LEFT JOIN users org ON org.id = m.organizer_id
                    LEFT JOIN departments d ON d.id = m.department_id
                    {$whereClause}
                    ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('Meeting::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT m.*, org.first_name AS organizer_first_name, org.last_name AS organizer_last_name,
                           d.name AS department_name
                    FROM meetings m
                    LEFT JOIN users org ON org.id = m.organizer_id
                    LEFT JOIN departments d ON d.id = m.department_id
                    WHERE m.id = :id AND m.deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('Meeting::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('meetings', $data);
        } catch (RuntimeException $e) {
            error_log('Meeting::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('meetings', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('Meeting::update - ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $db = Database::getInstance();
            $db->softDelete('meetings', 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('Meeting::delete - ' . $e->getMessage());
        }
    }
}
