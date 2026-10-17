<?php $tenantRows = $tenants ?? $recentTenants; ?>
<table class="table">
    <thead>
        <tr>
            <th>Company</th>
            <th>Email</th>
            <th>Status</th>
            <th>Plan</th>
            <th>Properties</th>
            <th>Published tours</th>
            <th>Users</th>
            <th>Created</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tenantRows as $row): ?>
            <tr>
                <td><?php echo e($row['name']); ?></td>
                <td><?php echo e($row['email']); ?></td>
                <td><span class="badge badge-<?php echo $row['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($row['status']); ?></span></td>
                <td><?php echo e($row['plan_name'] ?: '—'); ?> <span class="muted"><?php echo e($row['subscription_status']); ?></span></td>
                <td><?php echo (int) $row['properties_count']; ?></td>
                <td><?php echo (int) $row['active_tours_count']; ?></td>
                <td><?php echo (int) $row['users_count']; ?></td>
                <td><?php echo e(format_date($row['created_at'], 'Y-m-d')); ?></td>
                <td><a href="<?php echo base_url('?route=admin/tenant-view&id=' . $row['id']); ?>" class="btn btn-sm btn-primary">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$tenantRows): ?>
            <tr><td colspan="9" class="text-center">No tenants yet</td></tr>
        <?php endif; ?>
    </tbody>
</table>
