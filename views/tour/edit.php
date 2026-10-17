<?php
$pageTitle = 'Edit Tour';
ob_start();
?>

<h2 class="mb-3">Edit Tour</h2>

<div class="card">
    <form method="POST">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="property_id">Property *</label>
            <select id="property_id" name="property_id" class="form-select" required>
                <?php foreach ($properties as $property): ?>
                    <option value="<?php echo (int) $property['id']; ?>" <?php echo $tour['property_id'] == $property['id'] ? 'selected' : ''; ?>>
                        <?php echo e($property['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="title">Tour Title *</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo e($tour['title']); ?>" required maxlength="255">
            <label class="muted"><input type="checkbox" name="regenerate_slug" value="1"> Update the public link to match a new title (old shared links will stop working)</label>
            <div class="muted">Current link: <?php echo e(tour_url($tour['slug'])); ?></div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?php echo e($tour['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Status *</label>
            <select id="status" name="status" class="form-select" required>
                <option value="draft" <?php echo $tour['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo $tour['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
            <small class="muted">Published tours count towards your plan's active tour limit.</small>
        </div>

        <?php require __DIR__ . '/_options.php'; ?>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Update Tour</button>
            <a href="<?php echo base_url('?route=tour/view&id=' . $tour['id']); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
