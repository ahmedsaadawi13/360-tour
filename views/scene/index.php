<?php
$pageTitle = 'Scenes - ' . $tour['title'];
ob_start();
?>

<h2 class="mb-3">Scenes for: <?php echo e($tour['title']); ?></h2>

<div class="flex-between mb-3">
    <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>" class="btn btn-secondary">← Back to Tour</a>
    <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>" class="btn btn-primary">+ Add Scene</a>
</div>

<div class="card">
    <?php if (!empty($scenes)): ?>
        <p class="muted mb-2">The first scene is where visitors start. Use the arrows to change the order.</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($scenes as $i => $scene): ?>
                <div class="scene-card">
                    <img src="<?php echo e(asset($scene['thumbnail_path'] ?: $scene['image_path'])); ?>" alt="" loading="lazy">
                    <h4><?php echo $i + 1; ?>. <?php echo e($scene['name']); ?></h4>
                    <div class="flex gap-1 mt-1">
                        <a href="<?php echo base_url('?route=scene/edit&id=' . $scene['id']); ?>" class="btn btn-sm btn-primary" style="flex: 1;">Edit</a>
                        <?php if ($i > 0): ?>
                            <?php echo post_button(base_url('?route=scene/move'), '↑', 'btn btn-sm btn-secondary', null, ['id' => $scene['id'], 'direction' => 'up']); ?>
                        <?php endif; ?>
                        <?php if ($i < count($scenes) - 1): ?>
                            <?php echo post_button(base_url('?route=scene/move'), '↓', 'btn btn-sm btn-secondary', null, ['id' => $scene['id'], 'direction' => 'down']); ?>
                        <?php endif; ?>
                        <?php echo post_button(base_url('?route=scene/delete'), 'Delete', 'btn btn-sm btn-danger', 'Delete this scene and its hotspots?', ['id' => $scene['id']]); ?>
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
