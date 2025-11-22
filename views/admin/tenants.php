<?php
$pageTitle = 'Manage Tenants';
ob_start();
?>

<h2 class="mb-3">Tenants</h2>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Email</th>
                <th>Status</th>
                <th>Properties</th>
                <th>Tours</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tenants as $tenant): ?>
                <tr>
                    <td><?php echo e($tenant['name']); ?></td>
                    <td><?php echo e($tenant['email']); ?></td>
                    <td><span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($tenant['status']); ?></span></td>
                    <td><?php echo $tenant['usage']['properties']; ?></td>
                    <td><?php echo $tenant['usage']['active_tours']; ?></td>
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
