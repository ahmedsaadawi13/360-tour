<?php
/**
 * Property Model
 */

require_once __DIR__ . '/BaseModel.php';

class Property extends BaseModel
{
    protected $table = 'properties';
    protected $fillable = [
        'tenant_id', 'title', 'reference_code', 'type', 'status', 'price', 'currency',
        'city', 'area', 'address', 'bedrooms', 'bathrooms', 'size', 'description',
        'main_image', 'created_by'
    ];

    /**
     * Get properties by tenant with filters
     */
    public function getByTenantFiltered($tenantId, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = ?";
        $params = [$tenantId];

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND city = ?";
            $params[] = $filters['city'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR reference_code LIKE ? OR address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get property with tours count
     */
    public function getWithToursCount($propertyId, $tenantId)
    {
        $sql = "SELECT p.*, COUNT(t.id) as tours_count
                FROM {$this->table} p
                LEFT JOIN tours t ON p.id = t.property_id
                WHERE p.id = ? AND p.tenant_id = ?
                GROUP BY p.id";

        $stmt = $this->query($sql, [$propertyId, $tenantId]);
        return $stmt->fetch();
    }
}
