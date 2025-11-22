<?php
$pageTitle = 'Create Tour';
ob_start();
?>

<h2 class="mb-3">Create New Tour</h2>

<div class="card">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Property *</label>
            <select name="property_id" class="form-select" required>
                <option value="">Select a property...</option>
                <?php foreach ($properties as $property): ?>
                    <option value="<?php echo $property['id']; ?>" <?php echo $selectedPropertyId == $property['id'] ? 'selected' : ''; ?>>
                        <?php echo e($property['title']); ?> (<?php echo e($property['reference_code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Tour Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-select" required>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_public" value="1" checked>
                Make this tour publicly accessible
            </label>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Create Tour</button>
            <a href="<?php echo base_url('?route=tour'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
