<?php
$pageTitle = 'Tours';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Virtual Tours<?php echo $property ? ' — ' . e($property['title']) : ''; ?></h2>
    <div class="flex gap-1">
        <?php if ($property): ?>
            <a href="<?php echo base_url('?route=tour'); ?>" class="btn btn-secondary">All tours</a>
        <?php endif; ?>
        <a href="<?php echo base_url('?route=tour/create' . ($property ? '&property_id=' . $property['id'] : '')); ?>" class="btn btn-primary">+ Create Tour</a>
    </div>
</div>

<div class="card table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Property</th>
                <th>Status</th>
                <th>Scenes</th>
                <th>Views</th>
                <th>Leads</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tours)): ?>
                <?php foreach ($tours as $tour): ?>
                    <tr>
                        <td><a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>"><?php echo e($tour['title']); ?></a></td>
                        <td><?php echo e($tour['property_title']); ?></td>
                        <td><span class="badge <?php echo $tour['status'] === 'published' ? 'badge-success' : 'badge-warning'; ?>"><?php echo e($tour['status']); ?></span></td>
                        <td><?php echo (int) $tour['scenes_count']; ?></td>
                        <td><?php echo (int) $tour['views_count']; ?></td>
                        <td><?php echo (int) $tour['leads_count']; ?></td>
                        <td class="flex gap-1">
                            <a href="<?php echo base_url('?route=tour/edit&id=' . $tour['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="<?php echo e(tour_url($tour['slug'])); ?>" class="btn btn-sm btn-primary" target="_blank"><?php echo $tour['status'] === 'published' ? 'View' : 'Preview'; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No tours found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
