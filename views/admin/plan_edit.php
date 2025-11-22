<?php
$pageTitle = 'Edit Plan';
ob_start();
?>

<h2 class="mb-3">Edit Subscription Plan</h2>

<div class="card">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Plan Name *</label>
            <input type="text" name="name" class="form-control" value="<?php echo e($plan['name']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?php echo e($plan['description']); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Monthly Price *</label>
                <input type="number" step="0.01" name="price_monthly" class="form-control" value="<?php echo $plan['price_monthly']; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Yearly Price *</label>
                <input type="number" step="0.01" name="price_yearly" class="form-control" value="<?php echo $plan['price_yearly']; ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Max Properties *</label>
                <input type="number" name="max_properties" class="form-control" value="<?php echo $plan['max_properties']; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Max Active Tours *</label>
                <input type="number" name="max_active_tours" class="form-control" value="<?php echo $plan['max_active_tours']; ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Max Scenes *</label>
                <input type="number" name="max_scenes" class="form-control" value="<?php echo $plan['max_scenes']; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Max Users *</label>
                <input type="number" name="max_users" class="form-control" value="<?php echo $plan['max_users']; ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo $plan['sort_order']; ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo $plan['is_active'] ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Update Plan</button>
            <a href="<?php echo base_url('?route=admin/plans'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
