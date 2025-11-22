<?php
$pageTitle = 'Properties';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Properties</h2>
    <a href="<?php echo base_url('?route=property/create'); ?>" class="btn btn-primary">+ Add Property</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Status</th>
                <th>Price</th>
                <th>City</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($properties)): ?>
                <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><a href="<?php echo base_url('?route=property/view&id=' . $property['id']); ?>"><?php echo e($property['title']); ?></a></td>
                        <td><?php echo e($property['reference_code']); ?></td>
                        <td><?php echo e($property['type']); ?></td>
                        <td><span class="badge badge-info"><?php echo e($property['status']); ?></span></td>
                        <td><?php echo $property['price'] ? format_currency($property['price'], $property['currency']) : '-'; ?></td>
                        <td><?php echo e($property['city']); ?></td>
                        <td>
                            <a href="<?php echo base_url('?route=property/edit&id=' . $property['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="<?php echo base_url('?route=tour/create&property_id=' . $property['id']); ?>" class="btn btn-sm btn-primary">+ Tour</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No properties found. <a href="<?php echo base_url('?route=property/create'); ?>">Create one now</a></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
