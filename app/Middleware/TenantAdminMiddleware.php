<?php
/**
 * Tenant Admin Middleware
 *
 * Restricts billing, team and company settings to tenant admins (agents
 * manage content only).
 */

class TenantAdminMiddleware
{
    public static function handle()
    {
        TenantMiddleware::handle();

        if (!is_tenant_admin()) {
            flash('error', 'Only company administrators can access that page');
            redirect(base_url('?route=dashboard'));
        }
    }
}
