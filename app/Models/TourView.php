<?php
/**
 * Tour View Model
 *
 * One row per tour page load, used for analytics (views, unique visitors,
 * referrers). Visitor identity is a salted hash, no raw IP is stored.
 */

require_once __DIR__ . '/BaseModel.php';

class TourView extends BaseModel
{
    protected $table = 'tour_views';
    protected $fillable = ['tenant_id', 'tour_id', 'visitor_hash', 'referrer_host', 'is_embed', 'viewed_on'];

    /**
     * Record a view of a tour
     */
    public function record($tour, $isEmbed = false)
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget/i', $ua)) {
            return false;
        }

        $day = date('Y-m-d');
        $referrerHost = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST) ?: null;
        $ownHost = parse_url(config('base_url'), PHP_URL_HOST);
        if ($referrerHost === $ownHost) {
            $referrerHost = null;
        }

        $this->create([
            'tenant_id' => $tour['tenant_id'],
            'tour_id' => $tour['id'],
            'visitor_hash' => hash('sha256', config('app_key') . '|' . client_ip() . '|' . $ua . '|' . $day),
            'referrer_host' => $referrerHost ? substr($referrerHost, 0, 255) : null,
            'is_embed' => $isEmbed ? 1 : 0,
            'viewed_on' => $day,
        ]);

        return true;
    }

    /**
     * Views and unique visitors per day for the last $days days (zero-filled)
     */
    public function dailyStats($tourId, $days = 30)
    {
        $sql = "SELECT viewed_on, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
                FROM {$this->table}
                WHERE tour_id = ? AND viewed_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY viewed_on";

        $rows = [];
        foreach ($this->query($sql, [$tourId, $days - 1])->fetchAll() as $row) {
            $rows[$row['viewed_on']] = $row;
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date' => $day,
                'views' => (int) ($rows[$day]['views'] ?? 0),
                'visitors' => (int) ($rows[$day]['visitors'] ?? 0),
            ];
        }
        return $series;
    }

    /**
     * Totals for a tour (all time)
     */
    public function totals($tourId)
    {
        $sql = "SELECT COUNT(*) AS views,
                       COUNT(DISTINCT CONCAT(visitor_hash, viewed_on)) AS visitors,
                       SUM(is_embed) AS embed_views
                FROM {$this->table} WHERE tour_id = ?";
        $row = $this->query($sql, [$tourId])->fetch();
        return array_map('intval', $row);
    }

    /**
     * Top referring sites for a tour
     */
    public function topReferrers($tourId, $limit = 5)
    {
        $sql = "SELECT referrer_host, COUNT(*) AS views FROM {$this->table}
                WHERE tour_id = ? AND referrer_host IS NOT NULL
                GROUP BY referrer_host ORDER BY views DESC LIMIT " . (int) $limit;
        return $this->query($sql, [$tourId])->fetchAll();
    }

    /**
     * Tenant-wide views in the last $days days
     */
    public function tenantViews($tenantId, $days = 30)
    {
        $sql = "SELECT COUNT(*) AS views FROM {$this->table}
                WHERE tenant_id = ? AND viewed_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        return (int) $this->query($sql, [$tenantId, $days - 1])->fetch()['views'];
    }
}
