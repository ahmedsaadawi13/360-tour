<?php
/**
 * Platform Admin Controller
 *
 * Handles platform administration functions
 */

require_once __DIR__ . '/../Middleware/AdminMiddleware.php';
require_once __DIR__ . '/../Models/Tenant.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Plan.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';
require_once __DIR__ . '/../Models/Property.php';
require_once __DIR__ . '/../Models/Tour.php';

class AdminController
{
    private $tenantModel;
    private $userModel;
    private $planModel;
    private $subscriptionModel;
    private $propertyModel;
    private $tourModel;

    public function __construct()
    {
        AdminMiddleware::handle();

        $this->tenantModel = new Tenant();
        $this->userModel = new User();
        $this->planModel = new Plan();
        $this->subscriptionModel = new TenantSubscription();
        $this->propertyModel = new Property();
        $this->tourModel = new Tour();
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_tenants' => $this->tenantModel->count(),
            'active_tenants' => $this->tenantModel->count(['status' => 'active']),
            'total_properties' => $this->propertyModel->count(),
            'total_tours' => $this->tourModel->count(),
            'active_subscriptions' => $this->subscriptionModel->count(['status' => 'active'])
        ];

        $recentTenants = $this->tenantModel->all('created_at', 'DESC');
        $recentTenants = array_slice($recentTenants, 0, 10);

        view('admin.dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants
        ]);
    }

    /**
     * List all tenants
     */
    public function tenants()
    {
        $tenants = $this->tenantModel->all('created_at', 'DESC');

        foreach ($tenants as &$tenant) {
            $tenant['usage'] = $this->tenantModel->getUsageStats($tenant['id']);
        }

        view('admin.tenants', ['tenants' => $tenants]);
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

        $usage = $this->tenantModel->getUsageStats($tenantId);
        $users = $this->userModel->getByTenant($tenantId);

        view('admin.tenant_view', [
            'tenant' => $tenant,
            'usage' => $usage,
            'users' => $users
        ]);
    }

    /**
     * Update tenant status
     */
    public function tenantStatus()
    {
        if (!is_post() || !csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=admin/tenants'));
        }

        $tenantId = (int) post('tenant_id');
        $status = sanitize(post('status'));

        $this->tenantModel->update($tenantId, ['status' => $status]);

        flash('success', 'Tenant status updated');
        redirect(base_url('?route=admin/tenant-view&id=' . $tenantId));
    }

    /**
     * List all plans
     */
    public function plans()
    {
        $plans = $this->planModel->all('sort_order', 'ASC');

        view('admin.plans', ['plans' => $plans]);
    }

    /**
     * Create plan
     */
    public function planCreate()
    {
        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=admin/plans'));
            }

            $slug = slugify(post('name'));

            $data = [
                'name' => sanitize(post('name')),
                'slug' => $slug,
                'description' => sanitize(post('description')),
                'price_monthly' => (float) post('price_monthly'),
                'price_yearly' => (float) post('price_yearly'),
                'max_properties' => (int) post('max_properties'),
                'max_active_tours' => (int) post('max_active_tours'),
                'max_scenes' => (int) post('max_scenes'),
                'max_users' => (int) post('max_users'),
                'is_active' => post('is_active') ? 1 : 0,
                'sort_order' => (int) post('sort_order')
            ];

            $this->planModel->create($data);

            flash('success', 'Plan created successfully');
            redirect(base_url('?route=admin/plans'));
        } else {
            view('admin.plan_create');
        }
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

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=admin/plan-edit&id=' . $planId));
            }

            $data = [
                'name' => sanitize(post('name')),
                'description' => sanitize(post('description')),
                'price_monthly' => (float) post('price_monthly'),
                'price_yearly' => (float) post('price_yearly'),
                'max_properties' => (int) post('max_properties'),
                'max_active_tours' => (int) post('max_active_tours'),
                'max_scenes' => (int) post('max_scenes'),
                'max_users' => (int) post('max_users'),
                'is_active' => post('is_active') ? 1 : 0,
                'sort_order' => (int) post('sort_order')
            ];

            $this->planModel->update($planId, $data);

            flash('success', 'Plan updated successfully');
            redirect(base_url('?route=admin/plans'));
        } else {
            view('admin.plan_edit', ['plan' => $plan]);
        }
    }
}
