<?php
$pageTitle = 'Tour Details';
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($tour['title']); ?></h2>
    <div class="flex gap-1">
        <a href="<?php echo base_url('?route=tour/edit&id=' . $tour['id']); ?>" class="btn btn-secondary">Edit</a>
        <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>" class="btn btn-primary">+ Add Scene</a>
        <?php if ($tour['status'] === 'published'): ?>
            <a href="<?php echo base_url('/tour/view/' . $tour['slug']); ?>" class="btn btn-success" target="_blank">View Tour</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <h3>Tour Information</h3>
    <p><strong>Property:</strong> <?php echo e($property['title']); ?></p>
    <p><strong>Status:</strong> <span class="badge <?php echo $tour['status'] === 'published' ? 'badge-success' : 'badge-warning'; ?>"><?php echo e($tour['status']); ?></span></p>
    <p><strong>Public URL:</strong> <?php echo base_url('/tour/view/' . $tour['slug']); ?></p>
    <p><strong>Views:</strong> <?php echo $tour['views_count']; ?></p>
    <?php if ($tour['description']): ?>
        <p><strong>Description:</strong><br><?php echo nl2br(e($tour['description'])); ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Scenes (<?php echo count($scenes); ?>)</h3>
    <?php if (!empty($scenes)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($scenes as $scene): ?>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 10px;">
                    <?php if ($scene['image_path']): ?>
                        <img src="<?php echo asset($scene['image_path']); ?>" alt="Scene" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                    <?php endif; ?>
                    <h4 style="font-size: 14px;"><?php echo e($scene['name']); ?></h4>
                    <a href="<?php echo base_url('?route=scene/edit&id=' . $scene['id']); ?>" class="btn btn-sm btn-secondary" style="width: 100%; margin-top: 10px;">Edit Scene</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No scenes yet. <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>">Add your first scene</a></p>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
