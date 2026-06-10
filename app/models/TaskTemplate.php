<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class TaskTemplate
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

            if (!empty($filters['category_id'])) {
                $conditions[] = 'tt.category_id = :category_id';
                $params[':category_id'] = $filters['category_id'];
            }
            if (!empty($filters['search'])) {
                $conditions[] = '(tt.name LIKE :search OR tt.description LIKE :search2)';
                $params[':search'] = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['is_active'])) {
                $conditions[] = 'tt.is_active = :is_active';
                $params[':is_active'] = $filters['is_active'];
            }

            $where = '';
            if (!empty($conditions)) {
                $where = 'WHERE ' . implode(' AND ', $conditions);
            }

            $countSql = "SELECT COUNT(*) as total FROM task_templates tt $where";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetch(PDO::FETCH_OBJ)->total;

            $offset = ($page - 1) * $perPage;
            $sql = "SELECT tt.*
                    FROM task_templates tt
                    $where
                    ORDER BY tt.name ASC
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
            error_log('TaskTemplate::getAll - ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function getById(int $id): ?object
    {
        try {
            $sql = "SELECT * FROM task_templates WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('TaskTemplate::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('task_templates', $data);
        } catch (RuntimeException $e) {
            error_log('TaskTemplate::create - ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            $db = Database::getInstance();
            $db->update('task_templates', $data, 'id = ?', [$id]);
        } catch (RuntimeException $e) {
            error_log('TaskTemplate::update - ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        try {
            $sql = "DELETE FROM task_templates WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (RuntimeException $e) {
            error_log('TaskTemplate::delete - ' . $e->getMessage());
        }
    }
}
