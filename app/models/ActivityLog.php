<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class ActivityLog
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get paginated activity logs with optional filters.
     *
     * @param int   $page
     * @param int   $perPage
     * @param array $filters Possible keys: search, user_id, action, module, date_from, date_to
     * @return array
     */
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search2 OR al.action LIKE :search3 OR al.module LIKE :search4 OR al.ip_address LIKE :search5)';
                $params['search'] = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
                $params['search3'] = '%' . $filters['search'] . '%';
                $params['search4'] = '%' . $filters['search'] . '%';
                $params['search5'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['user_id'])) {
                $where[] = 'al.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['action'])) {
                $where[] = 'al.action = :action';
                $params['action'] = $filters['action'];
            }
            if (!empty($filters['module'])) {
                $where[] = 'al.module = :module';
                $params['module'] = $filters['module'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'al.created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'al.created_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT al.*, u.first_name, u.last_name, u.avatar
                    FROM activity_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('ActivityLog::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get paginated activity logs for a specific user.
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
            $sql = "SELECT al.*, u.first_name, u.last_name
                    FROM activity_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    WHERE al.user_id = :user_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('ActivityLog::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activity logs.
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit): array
    {
        try {
            $sql = "SELECT al.*, u.first_name, u.last_name, u.avatar
                    FROM activity_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    ORDER BY al.created_at DESC
                    LIMIT :lim";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('ActivityLog::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new activity log entry.
     *
     * @param array $data
     * @return int The inserted log ID
     */
    public function create(array $data): int
    {
        try {
            $db = Database::getInstance();
            return $db->insert('activity_logs', $data);
        } catch (RuntimeException $e) {
            error_log('ActivityLog::create - ' . $e->getMessage());
            return 0;
        }
    }
}
