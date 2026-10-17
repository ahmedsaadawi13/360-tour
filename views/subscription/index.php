<?php
$pageTitle = 'Subscription';
$planLimit = function ($key) use ($subscription) {
    return $subscription ? format_limit($subscription[$key]) : '0';
};
ob_start();
?>

<h2 class="mb-3">Subscription</h2>

<?php if ($subscription && $subscription['status'] === 'trialing'): ?>
    <div class="alert alert-warning">
        <strong>Trial period:</strong> <?php echo (int) $trialDays; ?> day<?php echo $trialDays == 1 ? '' : 's'; ?> left. Choose a plan below to keep your tours online after the trial.
    </div>
<?php elseif (!$subscription): ?>
    <div class="alert alert-error">
        Your <?php echo $latest ? e($latest['status']) . ' ' : ''; ?>subscription is inactive. Public tours are offline until you choose a plan.
    </div>
<?php endif; ?>

<?php if ($subscription && $subscription['cancel_at_period_end']): ?>
    <div class="alert alert-info">
        Your subscription is set to end on <?php echo e(format_date($subscription['current_period_end'], 'M j, Y')); ?>.
        <?php echo post_button(base_url('?route=subscription/resume'), 'Keep my subscription', 'btn btn-sm btn-primary'); ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h3>Current Plan</h3>
        <?php if ($subscription): ?>
            <table class="table">
                <tr><td><strong>Plan</strong></td><td><?php echo e($subscription['name']); ?></td></tr>
                <tr><td><strong>Status</strong></td><td><span class="badge badge-<?php echo $subscription['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo e(humanize($subscription['status'])); ?></span></td></tr>
                <tr><td><strong>Billing Cycle</strong></td><td><?php echo e(ucfirst($subscription['billing_cycle'])); ?></td></tr>
                <tr><td><strong>Price</strong></td><td><?php echo $subscription['billing_cycle'] === 'monthly' ? format_currency($subscription['price_monthly']) . '/month' : format_currency($subscription['price_yearly']) . '/year'; ?></td></tr>
                <tr><td><strong><?php echo $subscription['status'] === 'trialing' ? 'Trial ends' : ($subscription['cancel_at_period_end'] ? 'Ends' : 'Renews'); ?></strong></td>
                    <td><?php echo e(format_date($subscription['current_period_end'], 'M j, Y')); ?></td></tr>
            </table>
            <?php if ($subscription['status'] !== 'trialing' && !$subscription['cancel_at_period_end']): ?>
                <div class="mt-2">
                    <?php echo post_button(base_url('?route=subscription/cancel'), 'Cancel subscription', 'btn btn-sm btn-danger', 'Cancel at the end of the current billing period?'); ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No active subscription</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Usage</h3>
        <table class="table">
            <tr><td><strong>Properties</strong></td><td><?php echo (int) $usage['properties']; ?> / <?php echo e($planLimit('max_properties')); ?></td></tr>
            <tr><td><strong>Published Tours</strong></td><td><?php echo (int) $usage['active_tours']; ?> / <?php echo e($planLimit('max_active_tours')); ?></td></tr>
            <tr><td><strong>Scenes</strong></td><td><?php echo (int) $usage['scenes']; ?> / <?php echo e($planLimit('max_scenes')); ?></td></tr>
            <tr><td><strong>Users</strong></td><td><?php echo (int) $usage['users']; ?> / <?php echo e($planLimit('max_users')); ?></td></tr>
        </table>
    </div>
</div>

<div class="card mt-2">
    <div class="flex-between">
        <h3>Plans</h3>
        <label>Billing:
            <select id="cycleSelect" class="form-select" style="width: auto; display: inline-block;">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly (save ~17%)</option>
            </select>
        </label>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php foreach ($plans as $plan): ?>
            <?php
            $isCurrent = $subscription && (int) $subscription['plan_id'] === (int) $plan['id'];
            $features = (new Plan())->getFeaturesArray($plan);
            ?>
            <div class="plan-card<?php echo $isCurrent ? ' current' : ''; ?>">
                <h4><?php echo e($plan['name']); ?><?php echo $isCurrent ? ' <span class="badge badge-info">current</span>' : ''; ?></h4>
                <p class="muted"><?php echo e($plan['description']); ?></p>
                <div class="plan-price" data-monthly="<?php echo format_currency($plan['price_monthly']); ?>/mo" data-yearly="<?php echo format_currency($plan['price_yearly']); ?>/yr">
                    <?php echo format_currency($plan['price_monthly']); ?>/mo
                </div>
                <ul>
                    <?php if ($features): ?>
                        <?php foreach ($features as $feature): ?>
                            <li>✓ <?php echo e($feature); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>✓ <?php echo e(format_limit($plan['max_properties'])); ?> properties</li>
                        <li>✓ <?php echo e(format_limit($plan['max_active_tours'])); ?> published tours</li>
                        <li>✓ <?php echo e(format_limit($plan['max_scenes'])); ?> scenes</li>
                        <li>✓ <?php echo e(format_limit($plan['max_users'])); ?> users</li>
                    <?php endif; ?>
                </ul>
                <form method="POST" action="<?php echo base_url('?route=subscription/change-plan'); ?>"
                      onsubmit="return confirm('Switch to the <?php echo e(addslashes($plan['name'])); ?> plan? Your card will be charged now.');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="plan_id" value="<?php echo (int) $plan['id']; ?>">
                    <input type="hidden" name="billing_cycle" value="monthly" class="cycle-input">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <?php echo $isCurrent ? ($subscription['status'] === 'trialing' ? 'Subscribe now' : 'Switch billing / renew') : 'Select Plan'; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = <<<'JS'
<script>
document.getElementById('cycleSelect').addEventListener('change', function () {
    var cycle = this.value;
    document.querySelectorAll('.cycle-input').forEach(function (i) { i.value = cycle; });
    document.querySelectorAll('.plan-price').forEach(function (p) { p.innerHTML = p.getAttribute('data-' + cycle); });
});
</script>
JS;
require __DIR__ . '/../layouts/main.php';
?>
