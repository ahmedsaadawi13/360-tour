<?php
/**
 * Subscription Controller
 *
 * Handles subscription and billing management
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';
require_once __DIR__ . '/../Models/Plan.php';
require_once __DIR__ . '/../Models/Invoice.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/Tenant.php';

class SubscriptionController
{
    private $subscriptionModel;
    private $planModel;
    private $invoiceModel;
    private $paymentModel;
    private $tenantModel;

    public function __construct()
    {
        TenantMiddleware::handle();

        $this->subscriptionModel = new TenantSubscription();
        $this->planModel = new Plan();
        $this->invoiceModel = new Invoice();
        $this->paymentModel = new Payment();
        $this->tenantModel = new Tenant();
    }

    /**
     * Show subscription overview
     */
    public function index()
    {
        $tenantId = current_tenant_id();

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);
        $trialDays = $this->subscriptionModel->getTrialDaysRemaining($tenantId);
        $usage = $this->tenantModel->getUsageStats($tenantId);
        $plans = $this->planModel->getActivePlans();

        view('subscription.index', [
            'subscription' => $subscription,
            'trialDays' => $trialDays,
            'usage' => $usage,
            'plans' => $plans
        ]);
    }

    /**
     * Change subscription plan
     */
    public function changePlan()
    {
        $tenantId = current_tenant_id();

        if (!is_post()) {
            redirect(base_url('?route=subscription'));
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=subscription'));
        }

        $newPlanId = (int) post('plan_id');
        $billingCycle = sanitize(post('billing_cycle'));

        $plan = $this->planModel->find($newPlanId);
        if (!$plan || !$plan['is_active']) {
            flash('error', 'Invalid plan selected');
            redirect(base_url('?route=subscription'));
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);

        if ($subscription) {
            // Update existing subscription
            $data = [
                'plan_id' => $newPlanId,
                'billing_cycle' => $billingCycle,
                'status' => 'active'
            ];

            $this->subscriptionModel->update($subscription['id'], $data);
        } else {
            // Create new subscription
            $this->subscriptionModel->createWithTrial($tenantId, $newPlanId, 0);
        }

        flash('success', 'Subscription plan changed successfully');
        redirect(base_url('?route=subscription'));
    }

    /**
     * Billing history
     */
    public function billing()
    {
        $tenantId = current_tenant_id();

        $invoices = $this->invoiceModel->getByTenant($tenantId);
        $payments = $this->paymentModel->getByTenant($tenantId);

        view('subscription.billing', [
            'invoices' => $invoices,
            'payments' => $payments
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        $tenantId = current_tenant_id();

        if (!is_post()) {
            redirect(base_url('?route=subscription'));
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=subscription'));
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);

        if ($subscription) {
            $data = [
                'status' => 'canceled',
                'canceled_at' => date('Y-m-d H:i:s')
            ];

            $this->subscriptionModel->update($subscription['id'], $data);

            flash('success', 'Subscription canceled. You can continue using the service until the end of your billing period.');
        } else {
            flash('error', 'No active subscription found');
        }

        redirect(base_url('?route=subscription'));
    }
}
