<?php
$pageTitle = 'Create Scene';
ob_start();
?>

<h2 class="mb-3">Add Scene to Tour: <?php echo e($tour['title']); ?></h2>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="name">Scene Name *</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Living Room, Bedroom, Kitchen" required maxlength="255">
        </div>

        <div class="form-group">
            <label class="form-label" for="image">360° Panoramic Image *</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
            <small class="muted">Equirectangular 2:1 panorama (e.g. 6000×3000) in JPG, PNG or WEBP, max <?php echo round(config('max_upload_size') / 1048576); ?>MB.
                You can set the starting view and place hotspots visually after uploading.</small>
        </div>

        <input type="hidden" name="initial_yaw" value="0">
        <input type="hidden" name="initial_pitch" value="0">

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Upload Scene</button>
            <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
