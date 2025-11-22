<?php
/**
 * Tenant Subscription Model
 */

require_once __DIR__ . '/BaseModel.php';

class TenantSubscription extends BaseModel
{
    protected $table = 'tenant_subscriptions';
    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billing_cycle',
        'trial_end', 'current_period_start', 'current_period_end', 'canceled_at'
    ];

    /**
     * Get active subscription for tenant
     */
    public function getActiveSubscription($tenantId)
    {
        $sql = "SELECT ts.*, p.*
                FROM {$this->table} ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = ?
                AND ts.status IN ('trialing', 'active')
                ORDER BY ts.created_at DESC
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
        $limit = $subscription[$quotaField] ?? 0;

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

        $trialEnd = strtotime($subscription['trial_end']);
        $now = time();
        $diff = $trialEnd - $now;

        return max(0, ceil($diff / 86400));
    }

    /**
     * Create subscription with trial period
     */
    public function createWithTrial($tenantId, $planId, $trialDays = 14)
    {
        $data = [
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_end' => date('Y-m-d H:i:s', strtotime("+{$trialDays} days")),
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => date('Y-m-d H:i:s', strtotime("+{$trialDays} days")),
        ];

        return $this->create($data);
    }
}
