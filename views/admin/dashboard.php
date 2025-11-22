<?php
$pageTitle = 'Platform Admin Dashboard';
ob_start();
?>

<h2 class="mb-3">Platform Administration</h2>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['total_tenants']; ?></h3>
        <p>Total Tenants</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['active_tenants']; ?></h3>
        <p>Active Tenants</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['total_properties']; ?></h3>
        <p>Total Properties</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['total_tours']; ?></h3>
        <p>Total Tours</p>
    </div>
</div>

<div class="card">
    <h3>Recent Tenants</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Email</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentTenants as $tenant): ?>
                <tr>
                    <td><?php echo e($tenant['name']); ?></td>
                    <td><?php echo e($tenant['email']); ?></td>
                    <td><span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($tenant['status']); ?></span></td>
                    <td><?php echo format_date($tenant['created_at'], 'Y-m-d'); ?></td>
                    <td>
                        <a href="<?php echo base_url('?route=admin/tenant-view&id=' . $tenant['id']); ?>" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
