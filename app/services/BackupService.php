<?php

namespace App\Services;

use App\Helpers\Database;
use RuntimeException;

class BackupService
{
    private Database $db;
    private string $backupDir;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $this->backupDir = $app['backup']['path'];

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0775, true);
        }
    }

    public function createBackup(int $userId, string $type = 'manual'): array
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupDir . '/' . $filename;

        try {
            $dump = $this->generateSqlDump($config);
            file_put_contents($filepath, $dump);

            $zipFilename = str_replace('.sql', '.zip', $filename);
            $zipFilepath = $this->backupDir . '/' . $zipFilename;

            $zip = new \ZipArchive();
            if ($zip->open($zipFilepath, \ZipArchive::CREATE) === true) {
                $zip->addFile($filepath, $filename);
                $zip->close();
                unlink($filepath);
                $filepath = $zipFilepath;
                $filename = $zipFilename;
            }

            $fileSize = filesize($filepath);

            $this->db->insert('backup_history', [
                'file_name'  => $filename,
                'file_size'  => $fileSize,
                'file_path'  => $filepath,
                'type'       => $type,
                'created_by' => $userId,
                'status'     => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            logActivity('Backup', 'Database', null, null, json_encode(['file' => $filename, 'size' => $fileSize]));

            return ['success' => true, 'file' => $filename, 'path' => $filepath, 'size' => $fileSize];
        } catch (\Exception $e) {
            logError("Backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restoreBackup(int $backupId): array
    {
        $backup = $this->db->fetch("SELECT * FROM backup_history WHERE id = ?", [$backupId]);
        if (!$backup) {
            return ['success' => false, 'message' => 'Backup record not found'];
        }

        if (!file_exists($backup->file_path)) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }

        try {
            $sql = file_get_contents($backup->file_path);

            if (pathinfo($backup->file_path, PATHINFO_EXTENSION) === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($backup->file_path) === true) {
                    $sqlContent = $zip->getFromIndex(0);
                    $zip->close();
                    if ($sqlContent === false) {
                        return ['success' => false, 'message' => 'Could not extract SQL from archive'];
                    }
                    $sql = $sqlContent;
                } else {
                    return ['success' => false, 'message' => 'Could not open zip archive'];
                }
            }

            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $pdo = new \PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            logActivity('Restore', 'Database', $backupId, null, json_encode(['file' => $backup->file_name]));
            $this->db->update('backup_history', ['status' => 'restored'], 'id = ?', [$backupId]);

            return ['success' => true, 'message' => 'Database restored successfully'];
        } catch (\Exception $e) {
            logError("Restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function downloadBackup(int $backupId): ?string
    {
        $backup = $this->db->fetch("SELECT * FROM backup_history WHERE id = ?", [$backupId]);
        if (!$backup || !file_exists($backup->file_path)) {
            return null;
        }

        logActivity('Download Backup', 'Database', $backupId);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup->file_name . '"');
        header('Content-Length: ' . $backup->file_size);
        readfile($backup->file_path);
        exit;
    }

    public function getHistory(int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->db->fetch("SELECT COUNT(*) as cnt FROM backup_history");

        $items = $this->db->fetchAll(
            "SELECT bh.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name
             FROM backup_history bh
             LEFT JOIN users u ON bh.created_by = u.id
             ORDER BY bh.created_at DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        return [
            'items' => $items,
            'total' => (int)$total->cnt,
            'pages' => ceil((int)$total->cnt / $perPage),
        ];
    }

    public function deleteBackup(int $backupId): bool
    {
        $backup = $this->db->fetch("SELECT * FROM backup_history WHERE id = ?", [$backupId]);
        if (!$backup) {
            return false;
        }

        if (file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }

        $this->db->delete('backup_history', 'id = ?', [$backupId]);
        return true;
    }

    private function generateSqlDump(array $config): string
    {
        $pdo = new \PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $dump = "-- Task Management System Backup\n";
        $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $dump .= "\nDROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $createTable['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) continue;

            $columns = array_keys($rows[0]);
            $dump .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                $values[] = '(' . implode(', ', $escaped) . ')';
            }
            $dump .= implode(",\n", $values) . ";\n\n";
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $dump;
    }
}
