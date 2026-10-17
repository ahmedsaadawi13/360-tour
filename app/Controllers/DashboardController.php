<?php
/**
 * Dashboard Controller
 *
 * Main tenant dashboard
 */

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

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);
        $trialDays = $this->subscriptionModel->getTrialDaysRemaining($tenantId);
        $stats = $this->tenantModel->getUsageStats($tenantId);

        $recentProperties = array_slice($this->propertyModel->getByTenantFiltered($tenantId), 0, 5);
        $recentTours = array_slice($this->tourModel->getByTenant($tenantId), 0, 5);

        $leadModel = new Lead();
        $leadCounts = $leadModel->countByStatus($tenantId);
        $recentLeads = array_slice($leadModel->getByTenantFiltered($tenantId), 0, 5);

        view('tenant.dashboard', [
            'tenant' => $this->tenantModel->find($tenantId),
            'subscription' => $subscription,
            'trialDays' => $trialDays,
            'stats' => $stats,
            'views30' => (new TourView())->tenantViews($tenantId, 30),
            'leadCounts' => $leadCounts,
            'recentLeads' => $recentLeads,
            'recentProperties' => $recentProperties,
            'recentTours' => $recentTours
        ]);
    }
}
