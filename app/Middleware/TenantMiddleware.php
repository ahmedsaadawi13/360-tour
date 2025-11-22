<?php
/**
 * Tenant Middleware
 *
 * Ensures user belongs to a tenant (not platform admin accessing tenant routes)
 */

class TenantMiddleware
{
    public static function handle()
    {
        AuthMiddleware::handle();

        $user = current_user();
        if (!$user['tenant_id']) {
            flash('error', 'Access denied');
            redirect(base_url('?route=admin/dashboard'));
        }
    }
}
