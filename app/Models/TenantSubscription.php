<?php
/**
 * Tenant Subscription Model
 */

require_once __DIR__ . '/BaseModel.php';

class TenantSubscription extends BaseModel
{
    const STATUSES = ['trialing', 'active', 'past_due', 'canceled', 'expired'];
    const BILLING_CYCLES = ['monthly', 'yearly'];

    protected $table = 'tenant_subscriptions';
    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billing_cycle',
        'trial_end', 'current_period_start', 'current_period_end', 'canceled_at',
        'cancel_at_period_end'
    ];

    /**
     * Columns selected when joining a subscription with its plan.
     * Listed explicitly so plan columns never overwrite subscription columns
     * (a previous "ts.*, p.*" made $subscription['id'] equal the plan id).
     */
    private function joinedColumns()
    {
        return "ts.*, p.name, p.slug AS plan_slug, p.description AS plan_description,
                p.price_monthly, p.price_yearly, p.max_properties, p.max_active_tours,
                p.max_scenes, p.max_users, p.features";
    }

    /**
     * Get active (usable) subscription for tenant, joined with plan limits
     */
    public function getActiveSubscription($tenantId)
    {
        $sql = "SELECT {$this->joinedColumns()}
                FROM {$this->table} ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = ?
                AND ts.status IN ('trialing', 'active', 'past_due')
                ORDER BY ts.id DESC
                LIMIT 1";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetch();
    }

    /**
     * Get the most recent subscription regardless of status
     */
    public function getLatestSubscription($tenantId)
    {
        $sql = "SELECT {$this->joinedColumns()}
                FROM {$this->table} ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = ?
                ORDER BY ts.id DESC
                LIMIT 1";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetch();
    }

    /**
     * Check if tenant can perform action based on quota
     */
    public function checkQuota($tenantId, $quotaType, $currentCount)
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return false;
        }

        $quotaField = 'max_' . $quotaType;
        $limit = (int) ($subscription[$quotaField] ?? 0);

        // 9999 means unlimited
        if ($limit >= 9999) {
            return true;
        }

        return $currentCount < $limit;
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemaining($tenantId)
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription || $subscription['status'] !== 'trialing' || !$subscription['trial_end']) {
            return 0;
        }

        $diff = strtotime($subscription['trial_end']) - time();

        return (int) max(0, ceil($diff / 86400));
    }

    /**
     * Create subscription with trial period
     */
    public function createWithTrial($tenantId, $planId, $trialDays = 14)
    {
        $end = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));

        return $this->create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_end' => $end,
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => $end,
        ]);
    }

    /**
     * Subscriptions whose trial or billing period has ended (for the cron job)
     */
    public function getDueForRenewal()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status IN ('trialing', 'active', 'past_due')
                AND current_period_end IS NOT NULL
                AND current_period_end <= NOW()";

        return $this->query($sql)->fetchAll();
    }

    /**
     * Expire subscriptions of one tenant whose trial ended or that were
     * canceled and reached the end of the paid period. Cheap enough to run on
     * each authenticated request; renewals (charges) happen in bin/cron.php.
     */
    public function expireOverdue($tenantId)
    {
        $this->query(
            "UPDATE {$this->table} SET status = 'expired'
             WHERE tenant_id = ? AND status = 'trialing' AND trial_end IS NOT NULL AND trial_end <= NOW()",
            [$tenantId]
        );
        $this->query(
            "UPDATE {$this->table} SET status = 'canceled'
             WHERE tenant_id = ? AND status IN ('active', 'past_due') AND cancel_at_period_end = 1
             AND current_period_end IS NOT NULL AND current_period_end <= NOW()",
            [$tenantId]
        );
    }

    /**
     * Count subscriptions by status (admin dashboard)
     */
    public function countByStatus()
    {
        $rows = $this->query("SELECT status, COUNT(*) AS total FROM {$this->table} GROUP BY status")->fetchAll();
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
