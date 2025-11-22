<?php
/**
 * Admin Middleware
 *
 * Ensures user is a platform admin
 */

class AdminMiddleware
{
    public static function handle()
    {
        AuthMiddleware::handle();

        if (!has_role('platform_admin')) {
            flash('error', 'Access denied - Admin only');
            redirect(base_url('?route=dashboard'));
        }
    }
}
