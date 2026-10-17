<?php
/**
 * Tenant Model
 */

require_once __DIR__ . '/BaseModel.php';

class Tenant extends BaseModel
{
    const STATUSES = ['active', 'suspended', 'canceled'];

    protected $table = 'tenants';
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'logo', 'address', 'website',
        'brand_color', 'webhook_url', 'status'
    ];

    /**
     * Get tenant by slug
     */
    public function findBySlug($slug)
    {
        return $this->first(['slug' => $slug]);
    }

    /**
     * Generate a unique tenant slug
     */
    public function generateUniqueSlug($name)
    {
        $slug = slugify($name);
        $original = $slug;
        $counter = 1;

        while ($this->findBySlug($slug)) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Get tenant with its latest subscription.
     *
     * Subscription columns are aliased so they do not overwrite the tenant's
     * own id/status (previously "t.*, ts.*" did exactly that).
     */
    public function getWithSubscription($tenantId)
    {
        $sql = "SELECT t.*,
                       ts.id AS subscription_id, ts.status AS subscription_status,
                       ts.billing_cycle, ts.trial_end, ts.current_period_start,
                       ts.current_period_end, ts.cancel_at_period_end,
                       p.name AS plan_name
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON ts.id = (
                    SELECT MAX(id) FROM tenant_subscriptions WHERE tenant_id = t.id
                )
                LEFT JOIN plans p ON ts.plan_id = p.id
                WHERE t.id = ?
                LIMIT 1";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetch();
    }

    /**
     * All tenants with plan name and usage counts in a single query (admin list)
     */
    public function getAllWithUsage()
    {
        $sql = "SELECT t.*,
                       p.name AS plan_name, ts.status AS subscription_status,
                       (SELECT COUNT(*) FROM properties WHERE tenant_id = t.id) AS properties_count,
                       (SELECT COUNT(*) FROM tours WHERE tenant_id = t.id AND status = 'published') AS active_tours_count,
                       (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) AS users_count
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON ts.id = (
                    SELECT MAX(id) FROM tenant_subscriptions WHERE tenant_id = t.id
                )
                LEFT JOIN plans p ON ts.plan_id = p.id
                ORDER BY t.created_at DESC";

        return $this->query($sql)->fetchAll();
    }

    /**
     * Get usage statistics for tenant
     */
    public function getUsageStats($tenantId)
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM properties WHERE tenant_id = ?) AS properties,
                    (SELECT COUNT(*) FROM tours WHERE tenant_id = ? AND status = 'published') AS active_tours,
                    (SELECT COUNT(*) FROM scenes WHERE tenant_id = ?) AS scenes,
                    (SELECT COUNT(*) FROM users WHERE tenant_id = ?) AS users";

        $stats = $this->query($sql, array_fill(0, 4, $tenantId))->fetch();
        return array_map('intval', $stats);
    }
}
