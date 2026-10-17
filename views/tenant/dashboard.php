<?php
$pageTitle = 'Dashboard';
$limit = function ($key) use ($subscription) {
    return $subscription ? format_limit($subscription[$key]) : '0';
};
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($tenant['name']); ?></h2>
    <div class="flex gap-1">
        <a href="<?php echo base_url('?route=property/create'); ?>" class="btn btn-secondary">+ Property</a>
        <a href="<?php echo base_url('?route=tour/create'); ?>" class="btn btn-primary">+ Tour</a>
    </div>
</div>

<?php if (!$subscription): ?>
    <div class="alert alert-error">
        Your subscription is inactive — public tours are offline and you cannot add content.
        <?php if (is_tenant_admin()): ?><a href="<?php echo base_url('?route=subscription'); ?>">Choose a plan</a> to continue.<?php else: ?>Please contact your company administrator.<?php endif; ?>
    </div>
<?php elseif ($subscription['status'] === 'trialing'): ?>
    <div class="alert alert-warning">
        Your trial ends in <?php echo (int) $trialDays; ?> day<?php echo $trialDays == 1 ? '' : 's'; ?>.
        <?php if (is_tenant_admin()): ?><a href="<?php echo base_url('?route=subscription'); ?>">Upgrade now</a> to keep your tours online.<?php endif; ?>
    </div>
<?php elseif ($subscription['status'] === 'past_due'): ?>
    <div class="alert alert-error">Your last payment failed. Please update your billing to avoid interruption.</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo (int) $stats['properties']; ?></h3>
        <p>Properties</p>
        <small>Limit: <?php echo e($limit('max_properties')); ?></small>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $stats['active_tours']; ?></h3>
        <p>Published Tours</p>
        <small>Limit: <?php echo e($limit('max_active_tours')); ?></small>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $stats['scenes']; ?></h3>
        <p>Scenes</p>
        <small>Limit: <?php echo e($limit('max_scenes')); ?></small>
    </div>
    <div class="stat-card">
        <h3><?php echo number_format($views30); ?></h3>
        <p>Tour Views</p>
        <small>Last 30 days</small>
    </div>
    <div class="stat-card">
        <h3><?php echo (int) $leadCounts['new']; ?></h3>
        <p>New Leads</p>
        <small><a href="<?php echo base_url('?route=lead&status=new'); ?>">Follow up →</a></small>
    </div>
</div>

<div class="grid-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Leads</h3>
        </div>
        <?php if ($recentLeads): ?>
            <table class="table">
                <tbody>
                    <?php foreach ($recentLeads as $lead): ?>
                        <tr>
                            <td><a href="<?php echo base_url('?route=lead/view&id=' . $lead['id']); ?>"><?php echo e($lead['name']); ?></a>
                                <div class="muted"><?php echo e($lead['tour_title']); ?></div></td>
                            <td><span class="badge badge-<?php echo $lead['status'] === 'new' ? 'warning' : 'info'; ?>"><?php echo e($lead['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="muted">No leads yet. Publish a tour with the lead form enabled.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Properties</h3>
        </div>
        <?php if (!empty($recentProperties)): ?>
            <table class="table">
                <tbody>
                    <?php foreach ($recentProperties as $property): ?>
                        <tr>
                            <td><a href="<?php echo base_url('?route=property/view&id=' . $property['id']); ?>"><?php echo e($property['title']); ?></a></td>
                            <td><span class="badge badge-info"><?php echo e(humanize($property['status'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No properties yet. <a href="<?php echo base_url('?route=property/create'); ?>">Create your first property</a></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Tours</h3>
        </div>
        <?php if (!empty($recentTours)): ?>
            <table class="table">
                <tbody>
                    <?php foreach ($recentTours as $tour): ?>
                        <tr>
                            <td><a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>"><?php echo e($tour['title']); ?></a>
                                <div class="muted"><?php echo (int) $tour['views_count']; ?> views</div></td>
                            <td><span class="badge <?php echo $tour['status'] === 'published' ? 'badge-success' : 'badge-warning'; ?>"><?php echo e($tour['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tours yet. <a href="<?php echo base_url('?route=tour/create'); ?>">Create your first tour</a></p>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
