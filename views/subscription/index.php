<?php
$pageTitle = 'Subscription';
ob_start();
?>

<h2 class="mb-3">Subscription & Billing</h2>

<?php if ($trialDays > 0 && $subscription['status'] === 'trialing'): ?>
    <div class="alert alert-warning">
        <strong>Trial Period:</strong> Your trial ends in <?php echo $trialDays; ?> days.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <h3>Current Plan</h3>
        <?php if ($subscription): ?>
            <table class="table">
                <tr>
                    <td><strong>Plan:</strong></td>
                    <td><?php echo e($subscription['name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td><span class="badge badge-<?php echo $subscription['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo e($subscription['status']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Billing Cycle:</strong></td>
                    <td><?php echo ucfirst($subscription['billing_cycle']); ?></td>
                </tr>
                <tr>
                    <td><strong>Price:</strong></td>
                    <td><?php echo $subscription['billing_cycle'] === 'monthly' ? format_currency($subscription['price_monthly']) . '/month' : format_currency($subscription['price_yearly']) . '/year'; ?></td>
                </tr>
            </table>
        <?php else: ?>
            <p>No active subscription</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Usage</h3>
        <table class="table">
            <tr>
                <td><strong>Properties:</strong></td>
                <td><?php echo $usage['properties']; ?> / <?php echo $subscription['max_properties'] >= 9999 ? 'Unlimited' : $subscription['max_properties']; ?></td>
            </tr>
            <tr>
                <td><strong>Active Tours:</strong></td>
                <td><?php echo $usage['active_tours']; ?> / <?php echo $subscription['max_active_tours'] >= 9999 ? 'Unlimited' : $subscription['max_active_tours']; ?></td>
            </tr>
            <tr>
                <td><strong>Scenes:</strong></td>
                <td><?php echo $usage['scenes']; ?> / <?php echo $subscription['max_scenes'] >= 9999 ? 'Unlimited' : $subscription['max_scenes']; ?></td>
            </tr>
            <tr>
                <td><strong>Users:</strong></td>
                <td><?php echo $usage['users']; ?> / <?php echo $subscription['max_users']; ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card mt-3">
    <h3>Available Plans</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php foreach ($plans as $plan): ?>
            <div style="border: 2px solid <?php echo $subscription && $subscription['plan_id'] == $plan['id'] ? 'var(--primary-color)' : 'var(--border-color)'; ?>; border-radius: 8px; padding: 20px;">
                <h4><?php echo e($plan['name']); ?></h4>
                <div style="font-size: 2rem; color: var(--primary-color); margin: 15px 0;">
                    <?php echo format_currency($plan['price_monthly']); ?><small style="font-size: 1rem;">/mo</small>
                </div>
                <ul style="list-style: none; padding: 0; margin: 15px 0;">
                    <li>✓ <?php echo $plan['max_properties'] >= 9999 ? 'Unlimited' : $plan['max_properties']; ?> Properties</li>
                    <li>✓ <?php echo $plan['max_active_tours'] >= 9999 ? 'Unlimited' : $plan['max_active_tours']; ?> Active Tours</li>
                    <li>✓ <?php echo $plan['max_scenes'] >= 9999 ? 'Unlimited' : $plan['max_scenes']; ?> Scenes</li>
                    <li>✓ <?php echo $plan['max_users']; ?> Users</li>
                </ul>
                <?php if ($subscription && $subscription['plan_id'] == $plan['id']): ?>
                    <button class="btn btn-secondary" style="width: 100%;" disabled>Current Plan</button>
                <?php else: ?>
                    <form method="POST" action="<?php echo base_url('?route=subscription/change-plan'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                        <input type="hidden" name="billing_cycle" value="monthly">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Select Plan</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
