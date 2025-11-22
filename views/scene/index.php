<?php
$pageTitle = 'Scenes - ' . e($tour['title']);
ob_start();
?>

<h2 class="mb-3">Scenes for: <?php echo e($tour['title']); ?></h2>

<div class="flex-between mb-3">
    <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>" class="btn btn-secondary">← Back to Tour</a>
    <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>" class="btn btn-primary">+ Add Scene</a>
</div>

<div class="card">
    <?php if (!empty($scenes)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($scenes as $scene): ?>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 15px;">
                    <?php if ($scene['image_path']): ?>
                        <img src="<?php echo asset($scene['image_path']); ?>" alt="Scene" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                    <?php endif; ?>
                    <h4><?php echo e($scene['name']); ?></h4>
                    <p style="font-size: 0.9rem; color: var(--gray);">Order: <?php echo $scene['order_index']; ?></p>
                    <div class="flex gap-1 mt-2">
                        <a href="<?php echo base_url('?route=scene/edit&id=' . $scene['id']); ?>" class="btn btn-sm btn-primary" style="flex: 1;">Edit</a>
                        <a href="<?php echo base_url('?route=scene/delete&id=' . $scene['id'] . '&csrf_token=' . csrf_token()); ?>" 
                           onclick="return confirm('Delete this scene?')" 
                           class="btn btn-sm btn-danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">No scenes yet. <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>">Add your first scene</a></p>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
