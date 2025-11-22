<?php
$user = current_user();
$isAdmin = $user && $user['role'] === 'platform_admin';
?>

<div class="sidebar">
    <div class="sidebar-brand">
        <h2>Splash360 Tour</h2>
    </div>

    <ul class="sidebar-menu">
        <?php if ($isAdmin): ?>
            <!-- Platform Admin Menu -->
            <li><a href="<?php echo base_url('?route=admin/dashboard'); ?>">Dashboard</a></li>
            <li><a href="<?php echo base_url('?route=admin/tenants'); ?>">Tenants</a></li>
            <li><a href="<?php echo base_url('?route=admin/plans'); ?>">Plans</a></li>
        <?php else: ?>
            <!-- Tenant Menu -->
            <li><a href="<?php echo base_url('?route=dashboard'); ?>">Dashboard</a></li>
            <li><a href="<?php echo base_url('?route=property'); ?>">Properties</a></li>
            <li><a href="<?php echo base_url('?route=tour'); ?>">Tours</a></li>
            <li><a href="<?php echo base_url('?route=subscription'); ?>">Subscription</a></li>
            <li><a href="<?php echo base_url('?route=subscription/billing'); ?>">Billing</a></li>
        <?php endif; ?>
    </ul>
</div>
