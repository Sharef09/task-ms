<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class EmployeeIdea
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['status'])) {
                $conditions[] = 'ei.status = :status';
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['submitted_by'])) {
                $conditions[] = 'ei.submitted_by = :submitted_by';
                $params[':submitted_by'] = $filters['submitted_by'];
            }
            if (!empty($filters['search'])) {
                $conditions[] = '(ei.title LIKE :search OR ei.description LIKE :search2)';
                $params[':search'] = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
            }

            $where = '';
            if (!empty($conditions)) {
                $where = 'WHERE ' . implode(' AND ', $conditions);
            }

            $countSql = "SELECT COUNT(*) as total FROM employee_ideas ei $where";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetch(PDO::FETCH_OBJ)->total;

            $offset = ($page - 1) * $perPage;
            $sql = "SELECT ei.*, sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name,
                           rev.first_name AS reviewer_first_name, rev.last_name AS reviewer_last_name
                    FROM employee_ideas ei
                    LEFT JOIN users sub ON sub.id = ei.submitted_by
                    LEFT JOIN users rev ON rev.id = ei.reviewed_by
                    $where
                    ORDER BY ei.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_OBJ);

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (RuntimeException $e) {
            error_log('EmployeeIdea::getAll - ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT ei.*, sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name,
                           rev.first_name AS reviewer_first_name, rev.last_name AS reviewer_last_name
                    FROM employee_ideas ei
                    LEFT JOIN users sub ON sub.id = ei.submitted_by
                    LEFT JOIN users rev ON rev.id = ei.reviewed_by
                    WHERE ei.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('EmployeeIdea::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('employee_ideas', $data);
        } catch (RuntimeException $e) {
            error_log('EmployeeIdea::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('employee_ideas', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('EmployeeIdea::update - ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $sql = "DELETE FROM employee_ideas WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('EmployeeIdea::delete - ' . $e->getMessage());
        }
    }
}
