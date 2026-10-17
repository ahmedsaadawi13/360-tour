<?php
/**
 * Authentication Middleware
 *
 * Ensures user is logged in before accessing protected routes. The session
 * copy of the user is refreshed from the database on every request so role
 * changes and deactivations take effect immediately.
 */

class AuthMiddleware
{
    public static function handle()
    {
        if (!is_authenticated()) {
            flash('error', 'Please login to continue');
            redirect(base_url('?route=auth/login'));
        }

        $userModel = new User();
        $user = $userModel->find(session_get('user_id'));

        if (!$user || $user['status'] !== 'active') {
            session_destroy_all();
            flash('error', 'Your account is no longer active');
            redirect(base_url('?route=auth/login'));
        }

        unset($user['password']);
        session_set('user', $user);
    }
}
