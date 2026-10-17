<?php
$pageTitle = 'Properties';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Properties</h2>
    <a href="<?php echo base_url('?route=property/create'); ?>" class="btn btn-primary">+ Add Property</a>
</div>

<form method="GET" action="<?php echo base_url(); ?>" class="card filters">
    <input type="hidden" name="route" value="property">
    <div class="form-group">
        <label class="form-label" for="search">Search</label>
        <input type="search" id="search" name="search" class="form-control" placeholder="Title, reference, address" value="<?php echo e($filters['search']); ?>">
    </div>
    <div class="form-group">
        <label class="form-label" for="ftype">Type</label>
        <select id="ftype" name="type" class="form-select">
            <option value="">All types</option>
            <?php foreach (Property::TYPES as $type): ?>
                <option value="<?php echo e($type); ?>" <?php echo $filters['type'] === $type ? 'selected' : ''; ?>><?php echo e(humanize($type)); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="fstatus">Status</label>
        <select id="fstatus" name="status" class="form-select">
            <option value="">All statuses</option>
            <?php foreach (Property::STATUSES as $status): ?>
                <option value="<?php echo e($status); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo e(humanize($status)); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="fcity">City</label>
        <select id="fcity" name="city" class="form-select">
            <option value="">All cities</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?php echo e($city); ?>" <?php echo $filters['city'] === $city ? 'selected' : ''; ?>><?php echo e($city); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="<?php echo base_url('?route=property'); ?>" class="btn btn-secondary">Reset</a>
</form>

<div class="card table-responsive">
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
                        <td><?php echo e(humanize($property['type'])); ?></td>
                        <td><span class="badge badge-info"><?php echo e(humanize($property['status'])); ?></span></td>
                        <td><?php echo $property['price'] !== null ? format_currency($property['price'], $property['currency']) : '-'; ?></td>
                        <td><?php echo e($property['city']); ?></td>
                        <td class="flex gap-1">
                            <a href="<?php echo base_url('?route=property/edit&id=' . $property['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="<?php echo base_url('?route=tour/create&property_id=' . $property['id']); ?>" class="btn btn-sm btn-primary">+ Tour</a>
                            <?php echo post_button(base_url('?route=property/delete'), 'Delete', 'btn btn-sm btn-danger', 'Delete this property and all its tours?', ['id' => $property['id']]); ?>
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
