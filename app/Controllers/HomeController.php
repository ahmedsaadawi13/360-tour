<?php
/**
 * Home Controller
 *
 * Handles public homepage, agency showcase pages and the health check
 */

class HomeController
{
    public function index()
    {
        // If logged in, redirect to dashboard
        if (is_authenticated()) {
            redirect(base_url(has_role('platform_admin') ? '?route=admin/dashboard' : '?route=dashboard'));
        }

        $plans = (new Plan())->getActivePlans();

        view('home', ['plans' => $plans]);
    }

    /**
     * Public agency page listing its published tours
     */
    public function agency($slug = null)
    {
        $tenant = $slug ? (new Tenant())->findBySlug($slug) : null;

        if (!$tenant || $tenant['status'] !== 'active'
            || !(new TenantSubscription())->getActiveSubscription($tenant['id'])) {
            abort_404();
        }

        $tours = (new Tour())->getPublicByTenant($tenant['id']);

        view('agency.show', ['tenant' => $tenant, 'tours' => $tours]);
    }

    /**
     * Health check for load balancers / Docker
     */
    public function health()
    {
        try {
            Database::getInstance()->getConnection()->query('SELECT 1');
            json_response(['status' => 'ok']);
        } catch (Throwable $e) {
            json_response(['status' => 'error'], 503);
        }
    }
}
