<?php
/**
 * Dashboard Controller
 *
 * Main tenant dashboard
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/Tenant.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';
require_once __DIR__ . '/../Models/Property.php';
require_once __DIR__ . '/../Models/Tour.php';

class DashboardController
{
    private $tenantModel;
    private $subscriptionModel;
    private $propertyModel;
    private $tourModel;

    public function __construct()
    {
        TenantMiddleware::handle();

        $this->tenantModel = new Tenant();
        $this->subscriptionModel = new TenantSubscription();
        $this->propertyModel = new Property();
        $this->tourModel = new Tour();
    }

    /**
     * Show main dashboard
     */
    public function index()
    {
        $tenantId = current_tenant_id();

        // Get subscription info
        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);
        $trialDays = $this->subscriptionModel->getTrialDaysRemaining($tenantId);

        // Get usage stats
        $stats = $this->tenantModel->getUsageStats($tenantId);

        // Get recent properties
        $recentProperties = $this->propertyModel->where(['tenant_id' => $tenantId], 'created_at', 'DESC');
        $recentProperties = array_slice($recentProperties, 0, 5);

        // Get recent tours
        $recentTours = $this->tourModel->getByTenant($tenantId);
        $recentTours = array_slice($recentTours, 0, 5);

        view('tenant.dashboard', [
            'subscription' => $subscription,
            'trialDays' => $trialDays,
            'stats' => $stats,
            'recentProperties' => $recentProperties,
            'recentTours' => $recentTours
        ]);
    }
}
