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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="card">
        <?php if ($property['main_image']): ?>
            <img src="<?php echo asset($property['main_image']); ?>" alt="Property image" style="width: 100%; border-radius: 8px; margin-bottom: 20px;">
        <?php endif; ?>

        <h3>Property Details</h3>
        <table class="table">
            <tr>
                <td><strong>Reference Code</strong></td>
                <td><?php echo e($property['reference_code']); ?></td>
            </tr>
            <tr>
                <td><strong>Type</strong></td>
                <td><?php echo e($property['type']); ?></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td><span class="badge badge-info"><?php echo e($property['status']); ?></span></td>
            </tr>
            <tr>
                <td><strong>Price</strong></td>
                <td><?php echo $property['price'] ? format_currency($property['price'], $property['currency']) : '-'; ?></td>
            </tr>
            <tr>
                <td><strong>Location</strong></td>
                <td><?php echo e($property['city']); ?>, <?php echo e($property['area']); ?></td>
            </tr>
            <tr>
                <td><strong>Address</strong></td>
                <td><?php echo e($property['address']); ?></td>
            </tr>
            <tr>
                <td><strong>Bedrooms</strong></td>
                <td><?php echo $property['bedrooms'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td><strong>Bathrooms</strong></td>
                <td><?php echo $property['bathrooms'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td><strong>Size</strong></td>
                <td><?php echo $property['size'] ? $property['size'] . ' m²' : '-'; ?></td>
            </tr>
        </table>

        <?php if ($property['description']): ?>
            <h3 class="mt-3">Description</h3>
            <p><?php echo nl2br(e($property['description'])); ?></p>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <h3>Tours (<?php echo $property['tours_count']; ?>)</h3>
            <a href="<?php echo base_url('?route=tour?property_id=' . $property['id']); ?>" class="btn btn-primary" style="width: 100%;">View All Tours</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
