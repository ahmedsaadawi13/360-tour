<?php
/**
 * Platform Admin Controller
 *
 * Handles platform administration functions
 */

class AdminController
{
    private $tenantModel;
    private $userModel;
    private $planModel;
    private $subscriptionModel;
    private $propertyModel;
    private $tourModel;
    private $invoiceModel;

    public function __construct()
    {
        AdminMiddleware::handle();

        $this->tenantModel = new Tenant();
        $this->userModel = new User();
        $this->planModel = new Plan();
        $this->subscriptionModel = new TenantSubscription();
        $this->propertyModel = new Property();
        $this->tourModel = new Tour();
        $this->invoiceModel = new Invoice();
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $subscriptions = $this->subscriptionModel->countByStatus();

        $stats = [
            'total_tenants' => $this->tenantModel->count(),
            'active_tenants' => $this->tenantModel->count(['status' => 'active']),
            'total_properties' => $this->propertyModel->count(),
            'total_tours' => $this->tourModel->count(),
            'published_tours' => $this->tourModel->count(['status' => 'published']),
            'active_subscriptions' => $subscriptions['active'],
            'trialing_subscriptions' => $subscriptions['trialing'],
            'revenue' => $this->invoiceModel->totalPaid(),
            'pending_invoices' => $this->invoiceModel->count(['status' => 'pending']),
        ];

        view('admin.dashboard', [
            'stats' => $stats,
            'recentTenants' => array_slice($this->tenantModel->getAllWithUsage(), 0, 10)
        ]);
    }

    /**
     * List all tenants
     */
    public function tenants()
    {
        view('admin.tenants', ['tenants' => $this->tenantModel->getAllWithUsage()]);
    }

    /**
     * View tenant details
     */
    public function tenantView()
    {
        $tenantId = (int) get_param('id');
        $tenant = $this->tenantModel->getWithSubscription($tenantId);

        if (!$tenant) {
            flash('error', 'Tenant not found');
            redirect(base_url('?route=admin/tenants'));
        }

        view('admin.tenant_view', [
            'tenant' => $tenant,
            'usage' => $this->tenantModel->getUsageStats($tenantId),
            'users' => $this->userModel->getByTenant($tenantId),
            'invoices' => array_slice($this->invoiceModel->getByTenant($tenantId), 0, 10),
        ]);
    }

    /**
     * Update tenant status
     */
    public function tenantStatus()
    {
        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/tenants'));
        }

        $tenantId = (int) post('tenant_id');
        $status = enum_value(post('status'), Tenant::STATUSES);

        if (!$status || !$this->tenantModel->find($tenantId)) {
            flash('error', 'Invalid tenant or status');
            redirect(base_url('?route=admin/tenants'));
        }

        $this->tenantModel->update($tenantId, ['status' => $status]);

        flash('success', 'Tenant status updated');
        redirect(base_url('?route=admin/tenant-view&id=' . $tenantId));
    }

    /**
     * Extend (or restart) a tenant's trial
     */
    public function tenantExtendTrial()
    {
        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/tenants'));
        }

        $tenantId = (int) post('tenant_id');
        $days = max(1, min(365, (int) post('days')));
        $latest = $this->subscriptionModel->getLatestSubscription($tenantId);

        if (!$latest) {
            flash('error', 'Tenant has no subscription to extend');
            redirect(base_url('?route=admin/tenant-view&id=' . $tenantId));
        }

        if ($latest['status'] === 'active') {
            flash('error', 'Tenant has a paid subscription; trials can only be extended for trialing/expired accounts');
            redirect(base_url('?route=admin/tenant-view&id=' . $tenantId));
        }

        $base = ($latest['status'] === 'trialing' && strtotime($latest['trial_end']) > time()) ? $latest['trial_end'] : 'now';
        $end = date('Y-m-d H:i:s', strtotime("{$base} +{$days} days"));

        $this->subscriptionModel->update($latest['id'], [
            'status' => 'trialing',
            'trial_end' => $end,
            'current_period_end' => $end,
            'cancel_at_period_end' => 0,
        ]);

        flash('success', "Trial extended until " . format_date($end, 'M j, Y'));
        redirect(base_url('?route=admin/tenant-view&id=' . $tenantId));
    }

    /**
     * List all plans
     */
    public function plans()
    {
        view('admin.plans', ['plans' => $this->planModel->all('sort_order', 'ASC')]);
    }

    /**
     * Create plan
     */
    public function planCreate()
    {
        if (!is_post()) {
            view('admin.plan_create');
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/plans'));
        }

        $data = $this->collectPlanInput();
        if ($data['name'] === '') {
            flash('error', 'Plan name is required');
            view('admin.plan_create');
            return;
        }

        $slug = slugify($data['name']);
        $base = $slug;
        $i = 1;
        while ($this->planModel->findBySlug($slug)) {
            $slug = $base . '-' . $i++;
        }
        $data['slug'] = $slug;

        $this->planModel->create($data);

        flash('success', 'Plan created successfully');
        redirect(base_url('?route=admin/plans'));
    }

    /**
     * Edit plan
     */
    public function planEdit()
    {
        $planId = (int) get_param('id');
        $plan = $this->planModel->find($planId);

        if (!$plan) {
            flash('error', 'Plan not found');
            redirect(base_url('?route=admin/plans'));
        }

        if (!is_post()) {
            view('admin.plan_edit', ['plan' => $plan]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/plan-edit&id=' . $planId));
        }

        $data = $this->collectPlanInput();
        if ($data['name'] === '') {
            flash('error', 'Plan name is required');
            view('admin.plan_edit', ['plan' => $plan]);
            return;
        }

        $this->planModel->update($planId, $data);

        flash('success', 'Plan updated successfully');
        redirect(base_url('?route=admin/plans'));
    }

    /**
     * All invoices across tenants
     */
    public function invoices()
    {
        $status = enum_value(get_param('status'), ['draft', 'pending', 'paid', 'overdue', 'canceled']);

        view('admin.invoices', [
            'invoices' => $this->invoiceModel->getAllWithTenant($status),
            'status' => $status,
        ]);
    }

    /**
     * Mark an invoice as paid (offline payment, e.g. bank transfer)
     */
    public function invoiceMarkPaid()
    {
        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/invoices'));
        }

        $invoice = $this->invoiceModel->find((int) post('id'));
        if (!$invoice || $invoice['status'] === 'paid') {
            flash('error', 'Invoice not found or already paid');
            redirect(base_url('?route=admin/invoices'));
        }

        $this->invoiceModel->markAsPaid($invoice['id']);
        (new Payment())->create([
            'tenant_id' => $invoice['tenant_id'],
            'invoice_id' => $invoice['id'],
            'amount' => $invoice['amount'],
            'currency' => $invoice['currency'],
            'method' => 'offline',
            'status' => 'completed',
            'notes' => 'Marked paid by ' . current_user()['email'],
        ]);

        // Reactivate a past-due subscription linked to the invoice
        if ($invoice['subscription_id']) {
            $sub = $this->subscriptionModel->find($invoice['subscription_id']);
            if ($sub && $sub['status'] === 'past_due') {
                $end = date('Y-m-d H:i:s', strtotime($sub['current_period_end'] . ($sub['billing_cycle'] === 'yearly' ? ' +1 year' : ' +1 month')));
                $this->subscriptionModel->update($sub['id'], [
                    'status' => 'active',
                    'current_period_start' => $sub['current_period_end'],
                    'current_period_end' => $end,
                ]);
            }
        }

        flash('success', "Invoice {$invoice['invoice_number']} marked as paid");
        redirect(base_url('?route=admin/invoices'));
    }

    private function collectPlanInput()
    {
        $features = array_values(array_filter(array_map('sanitize', preg_split('/\R/', (string) post('features')))));

        return [
            'name' => sanitize(post('name')),
            'description' => sanitize(post('description')),
            'price_monthly' => max(0, (float) post('price_monthly')),
            'price_yearly' => max(0, (float) post('price_yearly')),
            'max_properties' => max(0, (int) post('max_properties')),
            'max_active_tours' => max(0, (int) post('max_active_tours')),
            'max_scenes' => max(0, (int) post('max_scenes')),
            'max_users' => max(1, (int) post('max_users')),
            'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
            'is_active' => post('is_active') ? 1 : 0,
            'sort_order' => (int) post('sort_order')
        ];
    }
}
