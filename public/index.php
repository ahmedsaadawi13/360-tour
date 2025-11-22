<?php
/**
 * Front Controller
 *
 * Entry point for all requests
 */

// Start session
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload dependencies
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Router.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Initialize router
$router = new Router();

// =====================================================
// PUBLIC ROUTES
// =====================================================
$router->get('/', 'HomeController', 'index');
$router->get('/tour/view/:slug', 'TourController', 'viewer');

// =====================================================
// AUTH ROUTES
// =====================================================
$router->any('/auth/login', 'AuthController', 'login');
$router->any('/auth/register', 'AuthController', 'register');
$router->get('/auth/logout', 'AuthController', 'logout');
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
$router->get('/property/delete', 'PropertyController', 'delete');

// Tours
$router->get('/tour', 'TourController', 'index');
$router->any('/tour/create', 'TourController', 'create');
$router->get('/tour/view', 'TourController', 'view');
$router->any('/tour/edit', 'TourController', 'edit');
$router->get('/tour/delete', 'TourController', 'delete');

// Scenes
$router->get('/scene', 'SceneController', 'index');
$router->any('/scene/create', 'SceneController', 'create');
$router->any('/scene/edit', 'SceneController', 'edit');
$router->get('/scene/delete', 'SceneController', 'delete');

// Hotspots
$router->post('/hotspot/create', 'HotspotController', 'create');
$router->post('/hotspot/update', 'HotspotController', 'update');
$router->get('/hotspot/delete', 'HotspotController', 'delete');

// Subscription
$router->get('/subscription', 'SubscriptionController', 'index');
$router->post('/subscription/change-plan', 'SubscriptionController', 'changePlan');
$router->get('/subscription/billing', 'SubscriptionController', 'billing');
$router->post('/subscription/cancel', 'SubscriptionController', 'cancel');

// =====================================================
// ADMIN ROUTES (Platform Admin)
// =====================================================
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/tenants', 'AdminController', 'tenants');
$router->get('/admin/tenant-view', 'AdminController', 'tenantView');
$router->post('/admin/tenant-status', 'AdminController', 'tenantStatus');
$router->get('/admin/plans', 'AdminController', 'plans');
$router->any('/admin/plan-create', 'AdminController', 'planCreate');
$router->any('/admin/plan-edit', 'AdminController', 'planEdit');

// Dispatch the request
$router->dispatch();
