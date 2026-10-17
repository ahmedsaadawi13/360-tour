<?php
/**
 * Tenant Middleware
 *
 * Ensures user belongs to an active tenant (not platform admin accessing
 * tenant routes) and keeps the tenant's subscription status current.
 */

class TenantMiddleware
{
    public static function handle()
    {
        AuthMiddleware::handle();

        $user = current_user();
        if (empty($user['tenant_id'])) {
            flash('error', 'Access denied');
            redirect(base_url('?route=admin/dashboard'));
        }

        $tenant = (new Tenant())->find($user['tenant_id']);
        if (!$tenant || $tenant['status'] !== 'active') {
            session_destroy_all();
            flash('error', 'Your company account is suspended. Please contact support.');
            redirect(base_url('?route=auth/login'));
        }

        (new TenantSubscription())->expireOverdue($user['tenant_id']);
    }
}
