<?php
/**
 * Database Connection Handler
 * 
 * Provides PDO connection with prepared statements and query builder methods.
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private ?int $tenantId = null;
    private array $config;

    private function __construct()
    {
        $this->config = require CONFIG_PATH . '/database.php';
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $config = $this->config['connections']['mysql'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public function setTenantId(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Execute a query with prepared statements
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Insert and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        // Add tenant_id if multi-tenancy is enabled and tenant is set
        if ($this->tenantId !== null && $this->config['tenancy']['enabled']) {
            $data[$this->config['tenancy']['column']] = $this->tenantId;
        }

        // Escape column names with backticks to handle reserved words like 'key'
        $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public function update(string $table, array $data, array $where): int
    {
        // Escape column names with backticks
        $setParts = array_map(fn($col) => "`{$col}` = ?", array_keys($data));
        $set = implode(', ', $setParts);

        $whereParts = array_map(fn($col) => "`{$col}` = ?", array_keys($where));
        $whereClause = implode(' AND ', $whereParts);

        // Add tenant condition if enabled
        if ($this->tenantId !== null && $this->config['tenancy']['enabled']) {
            $whereClause .= " AND `{$this->config['tenancy']['column']}` = ?";
            $where[$this->config['tenancy']['column']] = $this->tenantId;
        }

        $sql = "UPDATE `{$table}` SET {$set} WHERE {$whereClause}";
        $params = array_merge(array_values($data), array_values($where));

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): int
    {
        // Escape column names with backticks
        $whereParts = array_map(fn($col) => "`{$col}` = ?", array_keys($where));
        $whereClause = implode(' AND ', $whereParts);

        // Add tenant condition if enabled
        if ($this->tenantId !== null && $this->config['tenancy']['enabled']) {
            $whereClause .= " AND `{$this->config['tenancy']['column']}` = ?";
            $where[$this->config['tenancy']['column']] = $this->tenantId;
        }

        $sql = "DELETE FROM `{$table}` WHERE {$whereClause}";
        return $this->query($sql, array_values($where))->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get PDO instance for advanced queries
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
