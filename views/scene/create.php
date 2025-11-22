<?php
$pageTitle = 'Create Scene';
ob_start();
?>

<h2 class="mb-3">Add Scene to Tour: <?php echo e($tour['title']); ?></h2>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Scene Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Living Room, Bedroom, Kitchen" required>
        </div>

        <div class="form-group">
            <label class="form-label">360° Panoramic Image *</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/jpg,image/png" required>
            <small>Upload an equirectangular 360° panoramic image (JPG or PNG)</small>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Initial Yaw (Horizontal Angle)</label>
                <input type="number" step="0.01" name="initial_yaw" class="form-control" value="0">
                <small>Default view angle (degrees, 0-360)</small>
            </div>

            <div class="form-group">
                <label class="form-label">Initial Pitch (Vertical Angle)</label>
                <input type="number" step="0.01" name="initial_pitch" class="form-control" value="0">
                <small>Default view angle (degrees, -90 to 90)</small>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Create Scene</button>
            <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
