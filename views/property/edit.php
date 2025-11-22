<?php
$pageTitle = 'Edit Property';
ob_start();
?>

<h2 class="mb-3">Edit Property</h2>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" value="<?php echo e($property['title']); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Reference Code</label>
                <input type="text" name="reference_code" class="form-control" value="<?php echo e($property['reference_code']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    <?php
                    $types = ['apartment', 'villa', 'office', 'land', 'penthouse', 'townhouse', 'warehouse', 'shop', 'other'];
                    foreach ($types as $type):
                    ?>
                        <option value="<?php echo $type; ?>" <?php echo $property['type'] === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <?php
                    $statuses = ['for_sale', 'for_rent', 'sold', 'rented', 'off_market'];
                    foreach ($statuses as $status):
                    ?>
                        <option value="<?php echo $status; ?>" <?php echo $property['status'] === $status ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $property['price']; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control" value="<?php echo e($property['currency']); ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?php echo e($property['city']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Area</label>
                <input type="text" name="area" class="form-control" value="<?php echo e($property['area']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?php echo e($property['address']); ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Bedrooms</label>
                <input type="number" name="bedrooms" class="form-control" value="<?php echo $property['bedrooms']; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Bathrooms</label>
                <input type="number" name="bathrooms" class="form-control" value="<?php echo $property['bathrooms']; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Size (m²)</label>
                <input type="number" step="0.01" name="size" class="form-control" value="<?php echo $property['size']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?php echo e($property['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Main Image</label>
            <?php if ($property['main_image']): ?>
                <div class="mb-1">
                    <img src="<?php echo asset($property['main_image']); ?>" alt="Current image" style="max-width: 200px; border-radius: 5px;">
                </div>
            <?php endif; ?>
            <input type="file" name="main_image" class="form-control" accept="image/*">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Update Property</button>
            <a href="<?php echo base_url('?route=property'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
