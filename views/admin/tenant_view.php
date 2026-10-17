<?php
$pageTitle = 'Tenant Details';
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($tenant['name']); ?></h2>
    <a href="<?php echo e(base_url('agency/' . $tenant['slug'])); ?>" target="_blank" class="btn btn-secondary">Public page</a>
</div>

<div class="grid-2-1">
    <div>
        <div class="card">
            <h3>Tenant Information</h3>
            <table class="table">
                <tr><td><strong>Company</strong></td><td><?php echo e($tenant['name']); ?></td></tr>
                <tr><td><strong>Email</strong></td><td><?php echo e($tenant['email']); ?></td></tr>
                <tr><td><strong>Phone</strong></td><td><?php echo e($tenant['phone']); ?></td></tr>
                <tr><td><strong>Status</strong></td><td><span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($tenant['status']); ?></span></td></tr>
                <tr><td><strong>Created</strong></td><td><?php echo e(format_date($tenant['created_at'])); ?></td></tr>
            </table>

            <form method="POST" action="<?php echo base_url('?route=admin/tenant-status'); ?>" class="filters">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tenant_id" value="<?php echo (int) $tenant['id']; ?>">
                <div class="form-group">
                    <label class="form-label" for="status">Change status</label>
                    <select id="status" name="status" class="form-select">
                        <?php foreach (Tenant::STATUSES as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $tenant['status'] === $status ? 'selected' : ''; ?>><?php echo e(ucfirst($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
            <p class="muted mt-1">Suspended tenants cannot log in and their public tours go offline.</p>
        </div>

        <div class="card table-responsive">
            <h3>Users</h3>
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $member): ?>
                        <tr>
                            <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></td>
                            <td><?php echo e($member['email']); ?></td>
                            <td><span class="badge badge-info"><?php echo e($member['role']); ?></span></td>
                            <td><span class="badge badge-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($member['status']); ?></span></td>
                            <td><?php echo e(format_date($member['last_login'], 'Y-m-d')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card table-responsive">
            <h3>Recent invoices</h3>
            <table class="table">
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo e($invoice['invoice_number']); ?></td>
                        <td><?php echo format_currency($invoice['amount'], $invoice['currency']); ?></td>
                        <td><span class="badge badge-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo e($invoice['status']); ?></span></td>
                        <td><?php echo e(format_date($invoice['created_at'], 'Y-m-d')); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$invoices): ?><tr><td class="muted">No invoices</td></tr><?php endif; ?>
            </table>
        </div>
    </div>

    <div>
        <div class="card">
            <h3>Subscription</h3>
            <?php if ($tenant['subscription_id']): ?>
                <table class="table">
                    <tr><td><strong>Plan</strong></td><td><?php echo e($tenant['plan_name']); ?></td></tr>
                    <tr><td><strong>Status</strong></td><td><?php echo e(humanize($tenant['subscription_status'])); ?><?php echo $tenant['cancel_at_period_end'] ? ' (cancels at period end)' : ''; ?></td></tr>
                    <tr><td><strong>Cycle</strong></td><td><?php echo e($tenant['billing_cycle']); ?></td></tr>
                    <tr><td><strong>Period end</strong></td><td><?php echo e(format_date($tenant['current_period_end'], 'Y-m-d')); ?></td></tr>
                </table>
                <?php if ($tenant['subscription_status'] !== 'active'): ?>
                    <form method="POST" action="<?php echo base_url('?route=admin/tenant-extend-trial'); ?>" class="filters mt-1">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="tenant_id" value="<?php echo (int) $tenant['id']; ?>">
                        <div class="form-group">
                            <label class="form-label" for="days">Extend trial (days)</label>
                            <input id="days" type="number" name="days" min="1" max="365" value="14" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-secondary">Extend</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted">No subscription</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Usage</h3>
            <table class="table">
                <tr><td><strong>Properties</strong></td><td><?php echo (int) $usage['properties']; ?></td></tr>
                <tr><td><strong>Published Tours</strong></td><td><?php echo (int) $usage['active_tours']; ?></td></tr>
                <tr><td><strong>Scenes</strong></td><td><?php echo (int) $usage['scenes']; ?></td></tr>
                <tr><td><strong>Users</strong></td><td><?php echo (int) $usage['users']; ?></td></tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
