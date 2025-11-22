<?php
$pageTitle = 'Dashboard';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Dashboard</h2>
</div>

<?php if ($trialDays > 0 && $subscription['status'] === 'trialing'): ?>
    <div class="alert alert-warning">
        Your trial ends in <?php echo $trialDays; ?> days. <a href="<?php echo base_url('?route=subscription'); ?>">Upgrade now</a> to continue using all features.
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['properties']; ?></h3>
        <p>Total Properties</p>
        <small>Limit: <?php echo $subscription['max_properties'] >= 9999 ? 'Unlimited' : $subscription['max_properties']; ?></small>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['active_tours']; ?></h3>
        <p>Active Tours</p>
        <small>Limit: <?php echo $subscription['max_active_tours'] >= 9999 ? 'Unlimited' : $subscription['max_active_tours']; ?></small>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['scenes']; ?></h3>
        <p>Total Scenes</p>
        <small>Limit: <?php echo $subscription['max_scenes'] >= 9999 ? 'Unlimited' : $subscription['max_scenes']; ?></small>
    </div>

    <div class="stat-card">
        <h3><?php echo $stats['users']; ?></h3>
        <p>Users</p>
        <small>Limit: <?php echo $subscription['max_users']; ?></small>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Properties</h3>
        </div>
        <?php if (!empty($recentProperties)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProperties as $property): ?>
                        <tr>
                            <td><a href="<?php echo base_url('?route=property/view&id=' . $property['id']); ?>"><?php echo e($property['title']); ?></a></td>
                            <td><?php echo e($property['type']); ?></td>
                            <td><span class="badge badge-info"><?php echo e($property['status']); ?></span></td>
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
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Property</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTours as $tour): ?>
                        <tr>
                            <td><a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>"><?php echo e($tour['title']); ?></a></td>
                            <td><?php echo e($tour['property_title']); ?></td>
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
