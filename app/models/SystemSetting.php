<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;
use RuntimeException;

class SystemSetting
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Get all system settings.
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM system_settings ORDER BY `setting_key` ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (RuntimeException $e) {
            error_log('SystemSetting::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single setting by key.
     *
     * @param string $key
     * @return object|null
     */
    public function get(string $key): ?object
    {
        try {
            $sql = "SELECT * FROM system_settings WHERE `setting_key` = :key";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':key', $key);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: null;
        } catch (RuntimeException $e) {
            error_log('SystemSetting::get - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set (insert or update) a single setting value.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set(string $key, string $value): bool
    {
        try {
            $sql = "INSERT INTO system_settings (`setting_key`, `setting_value`)
                    VALUES (:key, :value)
                    ON DUPLICATE KEY UPDATE `setting_value` = :value2";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':key', $key);
            $stmt->bindValue(':value', $value);
            $stmt->bindValue(':value2', $value);
            $stmt->execute();
            return true;
        } catch (RuntimeException $e) {
            error_log('SystemSetting::set - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set multiple settings at once.
     *
     * @param array $data Associative array of key => value
     * @return bool
     */
    public function setMultiple(array $data): bool
    {
        try {
            $this->conn->beginTransaction();
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
            $this->conn->commit();
            return true;
        } catch (RuntimeException $e) {
            $this->conn->rollBack();
            error_log('SystemSetting::setMultiple - ' . $e->getMessage());
            return false;
        }
    }
}
