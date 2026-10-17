<?php
$user = current_user();
$isAdmin = $user && $user['role'] === 'platform_admin';
$currentRoute = trim((string) get_param('route', ''), '/');

// Highlight the entry matching the current route or one of its sub-route prefixes
$menuLink = function ($route, $label, array $prefixes = []) use ($currentRoute) {
    $active = $currentRoute === $route;
    foreach ($prefixes as $prefix) {
        $active = $active || strpos($currentRoute, $prefix) === 0;
    }
    return '<li><a href="' . e(base_url('?route=' . $route)) . '"' . ($active ? ' class="active"' : '') . '>' . e($label) . '</a></li>';
};
?>

<nav class="sidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <h2><?php echo e(config('name')); ?></h2>
    </div>

    <ul class="sidebar-menu">
        <?php if ($isAdmin): ?>
            <?php echo $menuLink('admin/dashboard', 'Dashboard'); ?>
            <?php echo $menuLink('admin/tenants', 'Tenants', ['admin/tenant-']); ?>
            <?php echo $menuLink('admin/plans', 'Plans', ['admin/plan-']); ?>
            <?php echo $menuLink('admin/invoices', 'Invoices'); ?>
        <?php else: ?>
            <?php echo $menuLink('dashboard', 'Dashboard'); ?>
            <?php echo $menuLink('property', 'Properties', ['property/']); ?>
            <?php echo $menuLink('tour', 'Tours', ['tour/', 'scene']); ?>
            <?php echo $menuLink('lead', 'Leads', ['lead/']); ?>
            <?php if (is_tenant_admin()): ?>
                <li class="sidebar-section">Company</li>
                <?php echo $menuLink('team', 'Team', ['team/']); ?>
                <?php echo $menuLink('settings/company', 'Branding & Settings'); ?>
                <?php echo $menuLink('subscription', 'Subscription', ['subscription/invoice']); ?>
                <?php echo $menuLink('subscription/billing', 'Billing'); ?>
            <?php endif; ?>
        <?php endif; ?>
        <li class="sidebar-section">Account</li>
        <?php echo $menuLink('settings/profile', 'My Profile'); ?>
    </ul>
</nav>
