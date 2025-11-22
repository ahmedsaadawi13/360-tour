<?php
/**
 * Tenant Model
 */

require_once __DIR__ . '/BaseModel.php';

class Tenant extends BaseModel
{
    protected $table = 'tenants';
    protected $fillable = ['name', 'slug', 'email', 'phone', 'logo', 'address', 'website', 'status'];

    /**
     * Get tenant by slug
     */
    public function findBySlug($slug)
    {
        return $this->first(['slug' => $slug]);
    }

    /**
     * Get tenant with subscription
     */
    public function getWithSubscription($tenantId)
    {
        $sql = "SELECT t.*, ts.*, p.name as plan_name
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON t.id = ts.tenant_id
                LEFT JOIN plans p ON ts.plan_id = p.id
                WHERE t.id = ?
                LIMIT 1";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetch();
    }

    /**
     * Get usage statistics for tenant
     */
    public function getUsageStats($tenantId)
    {
        $stats = [];

        // Count properties
        $stmt = $this->query("SELECT COUNT(*) as count FROM properties WHERE tenant_id = ?", [$tenantId]);
        $stats['properties'] = $stmt->fetch()['count'];

        // Count active tours
        $stmt = $this->query("SELECT COUNT(*) as count FROM tours WHERE tenant_id = ? AND status = 'published'", [$tenantId]);
        $stats['active_tours'] = $stmt->fetch()['count'];

        // Count scenes
        $stmt = $this->query("SELECT COUNT(*) as count FROM scenes WHERE tenant_id = ?", [$tenantId]);
        $stats['scenes'] = $stmt->fetch()['count'];

        // Count users
        $stmt = $this->query("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?", [$tenantId]);
        $stats['users'] = $stmt->fetch()['count'];

        return $stats;
    }
}
