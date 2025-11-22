<?php
/**
 * Authentication Middleware
 *
 * Ensures user is logged in before accessing protected routes
 */

class AuthMiddleware
{
    public static function handle()
    {
        if (!is_authenticated()) {
            flash('error', 'Please login to continue');
            redirect(base_url('?route=auth/login'));
        }
    }
}
