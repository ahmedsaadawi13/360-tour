<?php
/**
 * Base Model Class
 *
 * Provides common database operations for all models
 */

class BaseModel
{
    protected $db;
    protected $table;
    protected $fillable = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all records
     */
    public function all($orderBy = 'id', $direction = 'DESC')
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create new record
     */
    public function create($data)
    {
        $data = $this->filterFillable($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * Update record by ID
     */
    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete record by ID
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Find where conditions match
     */
    public function where($conditions, $orderBy = 'id', $direction = 'DESC')
    {
        $whereParts = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }

        $whereClause = implode(' AND ', $whereParts);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY {$orderBy} {$direction}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    /**
     * Find single record where conditions match
     */
    public function first($conditions)
    {
        $whereParts = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }

        $whereClause = implode(' AND ', $whereParts);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetch();
    }

    /**
     * Count records
     */
    public function count($conditions = [])
    {
        if (empty($conditions)) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table}");
            $stmt->execute();
        } else {
            $whereParts = [];
            $values = [];

            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = ?";
                $values[] = $value;
            }

            $whereClause = implode(' AND ', $whereParts);
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereClause}");
            $stmt->execute($values);
        }

        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Execute custom query
     */
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
