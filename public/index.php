<?php
/**
 * Front Controller
 *
 * Entry point for all requests
 */

// When running with PHP's built-in server, serve existing static files directly
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __FILE__ && is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../app/bootstrap.php';

start_secure_session();

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN'); // relaxed for /tour/embed/* only

// Initialize router
$router = new Router();

// =====================================================
// PUBLIC ROUTES
// =====================================================
$router->get('/', 'HomeController', 'index');
$router->get('/health', 'HomeController', 'health');
$router->get('/agency/:slug', 'HomeController', 'agency');
$router->get('/tour/view/:slug', 'TourController', 'viewer');
$router->get('/tour/embed/:slug', 'TourController', 'embed');
$router->post('/lead/submit', 'LeadController', 'submit');

// =====================================================
// AUTH ROUTES
// =====================================================
$router->any('/auth/login', 'AuthController', 'login');
$router->any('/auth/register', 'AuthController', 'register');
$router->post('/auth/logout', 'AuthController', 'logout');
$router->any('/auth/forgot-password', 'AuthController', 'forgotPassword');
$router->any('/auth/reset-password', 'AuthController', 'resetPassword');

// =====================================================
// TENANT ROUTES (Protected)
// =====================================================
$router->get('/dashboard', 'DashboardController', 'index');

// Properties
$router->get('/property', 'PropertyController', 'index');
$router->any('/property/create', 'PropertyController', 'create');
$router->get('/property/view', 'PropertyController', 'view');
$router->any('/property/edit', 'PropertyController', 'edit');
$router->post('/property/delete', 'PropertyController', 'delete');

// Tours
$router->get('/tour', 'TourController', 'index');
$router->any('/tour/create', 'TourController', 'create');
$router->get('/tour/view', 'TourController', 'view');
$router->any('/tour/edit', 'TourController', 'edit');
$router->post('/tour/delete', 'TourController', 'delete');

// Scenes
$router->get('/scene', 'SceneController', 'index');
$router->any('/scene/create', 'SceneController', 'create');
$router->any('/scene/edit', 'SceneController', 'edit');
$router->post('/scene/delete', 'SceneController', 'delete');
$router->post('/scene/move', 'SceneController', 'move');
$router->post('/scene/set-view', 'SceneController', 'setView');

// Hotspots
$router->post('/hotspot/create', 'HotspotController', 'create');
$router->post('/hotspot/update', 'HotspotController', 'update');
$router->post('/hotspot/delete', 'HotspotController', 'delete');

// Leads
$router->get('/lead', 'LeadController', 'index');
$router->any('/lead/view', 'LeadController', 'view');
$router->get('/lead/export', 'LeadController', 'export');
$router->post('/lead/delete', 'LeadController', 'delete');

// Team (tenant admin)
$router->get('/team', 'UserController', 'index');
$router->any('/team/create', 'UserController', 'create');
$router->any('/team/edit', 'UserController', 'edit');
$router->post('/team/delete', 'UserController', 'delete');

// Settings
$router->any('/settings/company', 'SettingsController', 'company');
$router->any('/settings/profile', 'SettingsController', 'profile');

// Subscription
$router->get('/subscription', 'SubscriptionController', 'index');
$router->post('/subscription/change-plan', 'SubscriptionController', 'changePlan');
$router->get('/subscription/billing', 'SubscriptionController', 'billing');
$router->get('/subscription/invoice', 'SubscriptionController', 'invoice');
$router->post('/subscription/cancel', 'SubscriptionController', 'cancel');
$router->post('/subscription/resume', 'SubscriptionController', 'resume');

// =====================================================
// ADMIN ROUTES (Platform Admin)
// =====================================================
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/tenants', 'AdminController', 'tenants');
$router->get('/admin/tenant-view', 'AdminController', 'tenantView');
$router->post('/admin/tenant-status', 'AdminController', 'tenantStatus');
$router->post('/admin/tenant-extend-trial', 'AdminController', 'tenantExtendTrial');
$router->get('/admin/plans', 'AdminController', 'plans');
$router->any('/admin/plan-create', 'AdminController', 'planCreate');
$router->any('/admin/plan-edit', 'AdminController', 'planEdit');
$router->get('/admin/invoices', 'AdminController', 'invoices');
$router->post('/admin/invoice-mark-paid', 'AdminController', 'invoiceMarkPaid');

// Dispatch the request
try {
    $router->dispatch();
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    if (!is_production()) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        view('errors.500');
    }
}
