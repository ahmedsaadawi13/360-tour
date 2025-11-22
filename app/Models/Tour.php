<?php
/**
 * Tour Model
 */

require_once __DIR__ . '/BaseModel.php';

class Tour extends BaseModel
{
    protected $table = 'tours';
    protected $fillable = [
        'tenant_id', 'property_id', 'title', 'slug', 'description',
        'status', 'is_public', 'is_featured', 'created_by'
    ];

    /**
     * Get tour by slug
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT t.*, p.title as property_title, p.price, p.currency,
                p.city, p.area, p.address, p.type as property_type
                FROM {$this->table} t
                JOIN properties p ON t.property_id = p.id
                WHERE t.slug = ?
                LIMIT 1";

        $stmt = $this->query($sql, [$slug]);
        return $stmt->fetch();
    }

    /**
     * Get tours by property
     */
    public function getByProperty($propertyId, $tenantId)
    {
        return $this->where([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId
        ], 'created_at', 'DESC');
    }

    /**
     * Get tours by tenant
     */
    public function getByTenant($tenantId)
    {
        $sql = "SELECT t.*, p.title as property_title, p.reference_code
                FROM {$this->table} t
                JOIN properties p ON t.property_id = p.id
                WHERE t.tenant_id = ?
                ORDER BY t.created_at DESC";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Increment view count
     */
    public function incrementViews($tourId)
    {
        $sql = "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = ?";
        $this->query($sql, [$tourId]);
    }

    /**
     * Generate unique slug
     */
    public function generateUniqueSlug($title, $tenantId, $excludeId = null)
    {
        $slug = slugify($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ? AND tenant_id = ?";
            $params = [$slug, $tenantId];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->query($sql, $params);
            $existing = $stmt->fetch();

            if (!$existing) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
}
