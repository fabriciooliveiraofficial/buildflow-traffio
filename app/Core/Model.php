<?php
/**
 * Base Model
 * 
 * Provides common database operations with multi-tenancy support.
 */

namespace App\Core;

abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected bool $timestamps = true;
    protected bool $tenantScoped = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): array
    {
        $cols = implode(', ', $columns);
        $sql = "SELECT {$cols} FROM {$this->table}";

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $sql .= " WHERE tenant_id = ?";
            return $this->db->fetchAll($sql, [$this->db->getTenantId()]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Find by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $sql .= " AND tenant_id = ?";
            $params[] = $this->db->getTenantId();
        }

        return $this->db->fetch($sql, $params);
    }

    /**
     * Find by specific column
     */
    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $params = [$value];

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $sql .= " AND tenant_id = ?";
            $params[] = $this->db->getTenantId();
        }

        return $this->db->fetch($sql . " LIMIT 1", $params);
    }

    /**
     * Get records matching conditions
     */
    public function where(array $conditions, string $orderBy = null, int $limit = null): array
    {
        $where = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $where[] = "{$column} {$value[0]} ?";
                $params[] = $value[1];
            } else {
                $where[] = "{$column} = ?";
                $params[] = $value;
            }
        }

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $where[] = "tenant_id = ?";
            $params[] = $this->db->getTenantId();
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Create a new record
     */
    public function create(array $data): int
    {
        // Filter to fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));

        // Add timestamps
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): int
    {
        // Filter to fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));

        // Update timestamp
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $where = [$this->primaryKey => $id];

        return $this->db->update($this->table, $data, $where);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): int
    {
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        $where = [];
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $where[] = "tenant_id = ?";
            $params[] = $this->db->getTenantId();
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->db->fetch($sql, $params);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);

        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        $where = [];
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }

        if ($this->tenantScoped && $this->db->getTenantId()) {
            $where[] = "tenant_id = ?";
            $params[] = $this->db->getTenantId();
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
}
