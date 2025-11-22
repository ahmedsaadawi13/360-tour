<?php
$pageTitle = 'Tenant Details';
ob_start();
?>

<h2 class="mb-3"><?php echo e($tenant['name']); ?></h2>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="card mb-3">
            <h3>Tenant Information</h3>
            <table class="table">
                <tr>
                    <td><strong>Company:</strong></td>
                    <td><?php echo e($tenant['name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><?php echo e($tenant['email']); ?></td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td><?php echo e($tenant['phone']); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td><span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($tenant['status']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Created:</strong></td>
                    <td><?php echo format_date($tenant['created_at']); ?></td>
                </tr>
            </table>

            <form method="POST" action="<?php echo base_url('?route=admin/tenant-status'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                <div class="form-group">
                    <label class="form-label">Change Status:</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $tenant['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $tenant['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="canceled" <?php echo $tenant['status'] === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>

        <div class="card">
            <h3>Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td><span class="badge badge-info"><?php echo e($user['role']); ?></span></td>
                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($user['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card">
            <h3>Usage Statistics</h3>
            <table class="table">
                <tr>
                    <td><strong>Properties:</strong></td>
                    <td><?php echo $usage['properties']; ?></td>
                </tr>
                <tr>
                    <td><strong>Active Tours:</strong></td>
                    <td><?php echo $usage['active_tours']; ?></td>
                </tr>
                <tr>
                    <td><strong>Scenes:</strong></td>
                    <td><?php echo $usage['scenes']; ?></td>
                </tr>
                <tr>
                    <td><strong>Users:</strong></td>
                    <td><?php echo $usage['users']; ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
