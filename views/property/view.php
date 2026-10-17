<?php
$pageTitle = 'Property Details';
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($property['title']); ?></h2>
    <div class="flex gap-1">
        <a href="<?php echo base_url('?route=property/edit&id=' . $property['id']); ?>" class="btn btn-secondary">Edit</a>
        <a href="<?php echo base_url('?route=tour/create&property_id=' . $property['id']); ?>" class="btn btn-primary">+ Create Tour</a>
    </div>
</div>

<div class="grid-2-1">
    <div class="card">
        <?php if ($property['main_image']): ?>
            <img src="<?php echo e(asset($property['main_image'])); ?>" alt="Property image" style="width: 100%; border-radius: 8px; margin-bottom: 20px;">
        <?php endif; ?>

        <h3>Property Details</h3>
        <table class="table">
            <tr>
                <td><strong>Reference Code</strong></td>
                <td><?php echo e($property['reference_code']); ?></td>
            </tr>
            <tr>
                <td><strong>Type</strong></td>
                <td><?php echo e(humanize($property['type'])); ?></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td><span class="badge badge-info"><?php echo e(humanize($property['status'])); ?></span></td>
            </tr>
            <tr>
                <td><strong>Price</strong></td>
                <td><?php echo $property['price'] !== null ? format_currency($property['price'], $property['currency']) : '-'; ?></td>
            </tr>
            <tr>
                <td><strong>Location</strong></td>
                <td><?php echo e(implode(', ', array_filter([$property['city'], $property['area']]))); ?></td>
            </tr>
            <tr>
                <td><strong>Address</strong></td>
                <td><?php echo e($property['address']); ?></td>
            </tr>
            <tr>
                <td><strong>Bedrooms</strong></td>
                <td><?php echo e($property['bedrooms'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td><strong>Bathrooms</strong></td>
                <td><?php echo e($property['bathrooms'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td><strong>Size</strong></td>
                <td><?php echo $property['size'] ? e($property['size']) . ' m²' : '-'; ?></td>
            </tr>
        </table>

        <?php if ($property['description']): ?>
            <h3 class="mt-3">Description</h3>
            <p><?php echo nl2br(e($property['description'])); ?></p>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <h3 class="mb-2">Tours (<?php echo (int) $property['tours_count']; ?>)</h3>
            <?php foreach ($tours as $tour): ?>
                <div class="flex-between mb-1">
                    <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>"><?php echo e($tour['title']); ?></a>
                    <span class="badge <?php echo $tour['status'] === 'published' ? 'badge-success' : 'badge-warning'; ?>"><?php echo e($tour['status']); ?></span>
                </div>
            <?php endforeach; ?>
            <a href="<?php echo base_url('?route=tour/create&property_id=' . $property['id']); ?>" class="btn btn-primary mt-1" style="width: 100%;">+ Create Tour</a>
        </div>
        <div class="card">
            <?php echo post_button(base_url('?route=property/delete'), 'Delete property', 'btn btn-danger', 'Delete this property, its tours and all scenes?', ['id' => $property['id']]); ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
