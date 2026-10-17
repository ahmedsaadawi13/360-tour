<?php
$pageTitle = 'Admin Dashboard';
ob_start();
?>

<h2 class="mb-3">Platform Overview</h2>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo (int) $stats['total_tenants']; ?></h3>
        <p>Tenants</p>
        <small><?php echo (int) $stats['active_tenants']; ?> active</small>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $stats['active_subscriptions']; ?></h3>
        <p>Paying Subscriptions</p>
        <small><?php echo (int) $stats['trialing_subscriptions']; ?> on trial</small>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($stats['revenue']); ?></h3>
        <p>Revenue (paid invoices)</p>
        <small><a href="<?php echo base_url('?route=admin/invoices&status=pending'); ?>"><?php echo (int) $stats['pending_invoices']; ?> pending</a></small>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $stats['total_properties']; ?></h3>
        <p>Properties</p>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $stats['total_tours']; ?></h3>
        <p>Tours</p>
        <small><?php echo (int) $stats['published_tours']; ?> published</small>
    </div>
</div>

<div class="card table-responsive">
    <h3 class="mb-2">Recent Tenants</h3>
    <?php require __DIR__ . '/_tenant_table.php'; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
