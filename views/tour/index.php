<?php
$pageTitle = 'Tours';
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Virtual Tours</h2>
    <a href="<?php echo base_url('?route=tour/create'); ?>" class="btn btn-primary">+ Create Tour</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Property</th>
                <th>Status</th>
                <th>Views</th>
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
                        <td><?php echo $tour['views_count']; ?></td>
                        <td>
                            <a href="<?php echo base_url('?route=tour/edit&id=' . $tour['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if ($tour['status'] === 'published'): ?>
                                <a href="<?php echo base_url('/tour/view/' . $tour['slug']); ?>" class="btn btn-sm btn-primary" target="_blank">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No tours found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
