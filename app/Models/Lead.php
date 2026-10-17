<?php
/**
 * Lead Model
 *
 * Contact requests submitted by visitors from public tours
 */

require_once __DIR__ . '/BaseModel.php';

class Lead extends BaseModel
{
    const STATUSES = ['new', 'contacted', 'qualified', 'closed', 'lost'];

    protected $table = 'leads';
    protected $fillable = [
        'tenant_id', 'tour_id', 'property_id', 'name', 'email', 'phone',
        'message', 'status', 'notes', 'source', 'ip_address'
    ];

    /**
     * Leads of a tenant with tour/property titles, optionally filtered
     */
    public function getByTenantFiltered($tenantId, $filters = [])
    {
        $sql = "SELECT l.*, t.title AS tour_title, p.title AS property_title
                FROM {$this->table} l
                LEFT JOIN tours t ON l.tour_id = t.id
                LEFT JOIN properties p ON l.property_id = p.id
                WHERE l.tenant_id = ?";
        $params = [$tenantId];

        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['tour_id'])) {
            $sql .= " AND l.tour_id = ?";
            $params[] = (int) $filters['tour_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term);
        }

        $sql .= " ORDER BY l.created_at DESC, l.id DESC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Single lead with tour/property titles
     */
    public function findWithRelations($id, $tenantId)
    {
        $sql = "SELECT l.*, t.title AS tour_title, t.slug AS tour_slug, p.title AS property_title
                FROM {$this->table} l
                LEFT JOIN tours t ON l.tour_id = t.id
                LEFT JOIN properties p ON l.property_id = p.id
                WHERE l.id = ? AND l.tenant_id = ?";
        return $this->query($sql, [$id, $tenantId])->fetch();
    }

    /**
     * Number of leads submitted from an IP in the last hour (spam throttle)
     */
    public function countRecentFromIp($ip)
    {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table}
                WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        return (int) $this->query($sql, [$ip])->fetch()['c'];
    }

    /**
     * Lead counts per status for a tenant
     */
    public function countByStatus($tenantId)
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS total FROM {$this->table} WHERE tenant_id = ? GROUP BY status",
            [$tenantId]
        )->fetchAll();

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
