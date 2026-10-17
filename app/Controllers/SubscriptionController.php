<?php
/**
 * Subscription Controller
 *
 * Handles subscription and billing management (tenant admins only)
 */

class SubscriptionController
{
    private $subscriptionModel;
    private $planModel;
    private $invoiceModel;
    private $paymentModel;
    private $tenantModel;

    public function __construct()
    {
        TenantAdminMiddleware::handle();

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

        view('subscription.index', [
            'subscription' => $this->subscriptionModel->getActiveSubscription($tenantId),
            'latest' => $this->subscriptionModel->getLatestSubscription($tenantId),
            'trialDays' => $this->subscriptionModel->getTrialDaysRemaining($tenantId),
            'usage' => $this->tenantModel->getUsageStats($tenantId),
            'plans' => $this->planModel->getActivePlans(),
        ]);
    }

    /**
     * Change subscription plan (creates an invoice and charges it)
     */
    public function changePlan()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=subscription'));
        }

        $plan = $this->planModel->find((int) post('plan_id'));
        if (!$plan || !$plan['is_active']) {
            flash('error', 'Invalid plan selected');
            redirect(base_url('?route=subscription'));
        }

        $billingCycle = enum_value(post('billing_cycle'), TenantSubscription::BILLING_CYCLES, 'monthly');

        $current = $this->subscriptionModel->getActiveSubscription($tenantId);
        if ($current && $current['status'] === 'active' && (int) $current['plan_id'] === (int) $plan['id']
            && $current['billing_cycle'] === $billingCycle && !$current['cancel_at_period_end']) {
            flash('info', 'You are already on this plan.');
            redirect(base_url('?route=subscription'));
        }

        $result = (new BillingService())->changePlan($tenantId, $plan, $billingCycle);

        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect(base_url($result['success'] ? '?route=subscription/billing' : '?route=subscription'));
    }

    /**
     * Billing history
     */
    public function billing()
    {
        $tenantId = current_tenant_id();

        view('subscription.billing', [
            'invoices' => $this->invoiceModel->getByTenant($tenantId),
            'payments' => $this->paymentModel->getByTenant($tenantId)
        ]);
    }

    /**
     * Printable invoice
     */
    public function invoice()
    {
        $tenantId = current_tenant_id();
        $invoice = $this->invoiceModel->findForTenant((int) get_param('id'), $tenantId);

        if (!$invoice) {
            flash('error', 'Invoice not found');
            redirect(base_url('?route=subscription/billing'));
        }

        view('subscription.invoice', [
            'invoice' => $invoice,
            'tenant' => $this->tenantModel->find($tenantId),
        ]);
    }

    /**
     * Cancel at the end of the current period (access continues until then)
     */
    public function cancel()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=subscription'));
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);

        if (!$subscription) {
            flash('error', 'No active subscription found');
        } else {
            $this->subscriptionModel->update($subscription['id'], [
                'cancel_at_period_end' => 1,
                'canceled_at' => date('Y-m-d H:i:s'),
            ]);
            flash('success', 'Subscription canceled. You can keep using the service until '
                . format_date($subscription['current_period_end'], 'M j, Y') . '.');
        }

        redirect(base_url('?route=subscription'));
    }

    /**
     * Undo a pending cancellation
     */
    public function resume()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=subscription'));
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($tenantId);
        if ($subscription && $subscription['cancel_at_period_end']) {
            $this->subscriptionModel->update($subscription['id'], ['cancel_at_period_end' => 0, 'canceled_at' => null]);
            flash('success', 'Your subscription will renew automatically.');
        }

        redirect(base_url('?route=subscription'));
    }
}
