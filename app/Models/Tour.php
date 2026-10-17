<?php
/**
 * Tour Model
 */

require_once __DIR__ . '/BaseModel.php';

class Tour extends BaseModel
{
    const STATUSES = ['draft', 'published'];

    protected $table = 'tours';
    protected $fillable = [
        'tenant_id', 'property_id', 'title', 'slug', 'description',
        'status', 'is_public', 'is_featured', 'auto_rotate', 'lead_form_enabled', 'created_by'
    ];

    /**
     * Get tour by slug
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT t.*, p.title as property_title, p.price, p.currency,
                p.city, p.area, p.address, p.type as property_type, p.status as property_status,
                p.bedrooms, p.bathrooms, p.size,
                tn.name as tenant_name, tn.slug as tenant_slug, tn.logo as tenant_logo,
                tn.phone as tenant_phone, tn.email as tenant_email, tn.website as tenant_website,
                tn.brand_color, tn.status as tenant_status
                FROM {$this->table} t
                JOIN properties p ON t.property_id = p.id
                JOIN tenants tn ON t.tenant_id = tn.id
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
    public function getByTenant($tenantId, $propertyId = null)
    {
        $sql = "SELECT t.*, p.title as property_title, p.reference_code,
                (SELECT COUNT(*) FROM scenes s WHERE s.tour_id = t.id) AS scenes_count,
                (SELECT COUNT(*) FROM leads l WHERE l.tour_id = t.id) AS leads_count
                FROM {$this->table} t
                JOIN properties p ON t.property_id = p.id
                WHERE t.tenant_id = ?";
        $params = [$tenantId];

        if ($propertyId) {
            $sql .= " AND t.property_id = ?";
            $params[] = $propertyId;
        }

        $sql .= " ORDER BY t.created_at DESC, t.id DESC";

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Published public tours of a tenant (agency showcase page)
     */
    public function getPublicByTenant($tenantId)
    {
        $sql = "SELECT t.id, t.title, t.slug, t.description, t.views_count,
                p.title as property_title, p.city, p.area, p.price, p.currency, p.type as property_type,
                p.main_image,
                (SELECT COALESCE(s.thumbnail_path, s.image_path) FROM scenes s
                 WHERE s.tour_id = t.id ORDER BY s.order_index, s.id LIMIT 1) AS cover_image
                FROM {$this->table} t
                JOIN properties p ON t.property_id = p.id
                WHERE t.tenant_id = ? AND t.status = 'published' AND t.is_public = 1
                ORDER BY t.is_featured DESC, t.created_at DESC";

        return $this->query($sql, [$tenantId])->fetchAll();
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
    public function generateUniqueSlug($title, $tenantId = null, $excludeId = null)
    {
        // Slugs are globally unique: the public URL /tour/view/{slug} has no tenant part
        $slug = slugify($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
            $params = [$slug];

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
