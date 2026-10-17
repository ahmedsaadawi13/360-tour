<?php
/* Shared plan form; expects $plan (may be empty) and $submitLabel */
$p = function ($key, $default = '') use ($plan) {
    return $plan[$key] ?? $default;
};
$features = $plan ? implode("\n", (new Plan())->getFeaturesArray($plan)) : '';
?>
<form method="POST">
    <?php echo csrf_field(); ?>

    <div class="form-group">
        <label class="form-label" for="name">Plan Name *</label>
        <input id="name" type="text" name="name" class="form-control" value="<?php echo e($p('name')); ?>" required maxlength="100">
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="2"><?php echo e($p('description')); ?></textarea>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label" for="price_monthly">Monthly Price *</label>
            <input id="price_monthly" type="number" step="0.01" min="0" name="price_monthly" class="form-control" value="<?php echo e($p('price_monthly', '0')); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="price_yearly">Yearly Price *</label>
            <input id="price_yearly" type="number" step="0.01" min="0" name="price_yearly" class="form-control" value="<?php echo e($p('price_yearly', '0')); ?>" required>
        </div>
    </div>

    <div class="grid-2">
        <?php foreach (['max_properties' => ['Max Properties', 10], 'max_active_tours' => ['Max Published Tours', 5], 'max_scenes' => ['Max Scenes', 50], 'max_users' => ['Max Users', 2]] as $field => list($label, $default)): ?>
            <div class="form-group">
                <label class="form-label" for="<?php echo $field; ?>"><?php echo e($label); ?> *</label>
                <input id="<?php echo $field; ?>" type="number" min="0" name="<?php echo $field; ?>" class="form-control" value="<?php echo e($p($field, $default)); ?>" required>
                <small class="muted">Use 9999 for unlimited</small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <label class="form-label" for="features">Marketing features (one per line)</label>
        <textarea id="features" name="features" class="form-control" rows="5"><?php echo e($features); ?></textarea>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label" for="sort_order">Sort Order</label>
            <input id="sort_order" type="number" name="sort_order" class="form-control" value="<?php echo e($p('sort_order', '0')); ?>">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?php echo $p('is_active', 1) ? 'checked' : ''; ?>> Active (visible for sign-up)</label>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary"><?php echo e($submitLabel); ?></button>
        <a href="<?php echo base_url('?route=admin/plans'); ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>
