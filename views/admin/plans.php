<?php
$pageTitle = 'Subscription Plans';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Subscription Plans</h2>
    <a href="<?php echo base_url('?route=admin/plan-create'); ?>" class="btn btn-primary">+ Create Plan</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Monthly Price</th>
                <th>Yearly Price</th>
                <th>Properties</th>
                <th>Tours</th>
                <th>Scenes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><strong><?php echo e($plan['name']); ?></strong></td>
                    <td><?php echo format_currency($plan['price_monthly']); ?></td>
                    <td><?php echo format_currency($plan['price_yearly']); ?></td>
                    <td><?php echo $plan['max_properties'] >= 9999 ? 'Unlimited' : $plan['max_properties']; ?></td>
                    <td><?php echo $plan['max_active_tours'] >= 9999 ? 'Unlimited' : $plan['max_active_tours']; ?></td>
                    <td><?php echo $plan['max_scenes'] >= 9999 ? 'Unlimited' : $plan['max_scenes']; ?></td>
                    <td><span class="badge badge-<?php echo $plan['is_active'] ? 'success' : 'danger'; ?>"><?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <a href="<?php echo base_url('?route=admin/plan-edit&id=' . $plan['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
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
