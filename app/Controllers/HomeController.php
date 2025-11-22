<?php
/**
 * Home Controller
 *
 * Handles public homepage
 */

class HomeController
{
    public function index()
    {
        // If logged in, redirect to dashboard
        if (is_authenticated()) {
            $user = current_user();

            if ($user['role'] === 'platform_admin') {
                redirect(base_url('?route=admin/dashboard'));
            } else {
                redirect(base_url('?route=dashboard'));
            }
        }

        view('home');
    }
}
