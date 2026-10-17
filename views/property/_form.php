<?php
/**
 * Shared property form.
 * Expects $property (array of current values) and $submitLabel.
 */
$value = function ($key, $default = '') use ($property) {
    return $property[$key] ?? $default;
};
?>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="form-group">
        <label class="form-label" for="title">Title *</label>
        <input type="text" id="title" name="title" class="form-control" value="<?php echo e($value('title')); ?>" required maxlength="255">
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label" for="reference_code">Reference Code</label>
            <input type="text" id="reference_code" name="reference_code" class="form-control" value="<?php echo e($value('reference_code')); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label" for="type">Type *</label>
            <select id="type" name="type" class="form-select" required>
                <?php foreach (Property::TYPES as $type): ?>
                    <option value="<?php echo e($type); ?>" <?php echo $value('type', 'apartment') === $type ? 'selected' : ''; ?>><?php echo e(humanize($type)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid-3">
        <div class="form-group">
            <label class="form-label" for="status">Status *</label>
            <select id="status" name="status" class="form-select" required>
                <?php foreach (Property::STATUSES as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $value('status', 'for_sale') === $status ? 'selected' : ''; ?>><?php echo e(humanize($status)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="price">Price</label>
            <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" value="<?php echo e($value('price')); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="currency">Currency</label>
            <select id="currency" name="currency" class="form-select">
                <?php
                $currencies = config('currencies');
                $selectedCurrency = $value('currency', config('default_currency'));
                if (!in_array($selectedCurrency, $currencies, true)) {
                    $currencies[] = $selectedCurrency;
                }
                foreach ($currencies as $currency):
                ?>
                    <option value="<?php echo e($currency); ?>" <?php echo $selectedCurrency === $currency ? 'selected' : ''; ?>><?php echo e($currency); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label" for="city">City</label>
            <input type="text" id="city" name="city" class="form-control" value="<?php echo e($value('city')); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label" for="area">Area</label>
            <input type="text" id="area" name="area" class="form-control" value="<?php echo e($value('area')); ?>" maxlength="100">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="address">Address</label>
        <input type="text" id="address" name="address" class="form-control" value="<?php echo e($value('address')); ?>">
    </div>

    <div class="grid-3">
        <div class="form-group">
            <label class="form-label" for="bedrooms">Bedrooms</label>
            <input type="number" min="0" id="bedrooms" name="bedrooms" class="form-control" value="<?php echo e($value('bedrooms')); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="bathrooms">Bathrooms</label>
            <input type="number" min="0" id="bathrooms" name="bathrooms" class="form-control" value="<?php echo e($value('bathrooms')); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="size">Size (m²)</label>
            <input type="number" step="0.01" min="0" id="size" name="size" class="form-control" value="<?php echo e($value('size')); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="5"><?php echo e($value('description')); ?></textarea>
    </div>

    <div class="form-group">
        <label class="form-label" for="main_image">Main Image</label>
        <?php if ($value('main_image')): ?>
            <div class="mb-1">
                <img src="<?php echo e(asset($value('main_image'))); ?>" alt="Current image" style="max-width: 200px; border-radius: 5px;">
            </div>
        <?php endif; ?>
        <input type="file" id="main_image" name="main_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <small class="muted">JPG, PNG or WEBP, max <?php echo round(config('max_upload_size') / 1048576); ?>MB</small>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary"><?php echo e($submitLabel); ?></button>
        <a href="<?php echo base_url('?route=property'); ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>
