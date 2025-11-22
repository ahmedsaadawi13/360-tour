<?php
$pageTitle = 'Edit Tour';
ob_start();
?>

<h2 class="mb-3">Edit Tour</h2>

<div class="card">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Property *</label>
            <select name="property_id" class="form-select" required>
                <?php foreach ($properties as $property): ?>
                    <option value="<?php echo $property['id']; ?>" <?php echo $tour['property_id'] == $property['id'] ? 'selected' : ''; ?>>
                        <?php echo e($property['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Tour Title *</label>
            <input type="text" name="title" class="form-control" value="<?php echo e($tour['title']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?php echo e($tour['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-select" required>
                <option value="draft" <?php echo $tour['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo $tour['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_public" value="1" <?php echo $tour['is_public'] ? 'checked' : ''; ?>>
                Make this tour publicly accessible
            </label>
        </div>

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
