<?php
$pageTitle = 'Create Tour';
$tour = [];
ob_start();
?>

<h2 class="mb-3">Create Virtual Tour</h2>

<div class="card">
    <form method="POST">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="property_id">Property *</label>
            <select id="property_id" name="property_id" class="form-select" required>
                <?php foreach ($properties as $property): ?>
                    <option value="<?php echo (int) $property['id']; ?>" <?php echo $selectedPropertyId == $property['id'] ? 'selected' : ''; ?>>
                        <?php echo e($property['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="title">Tour Title *</label>
            <input type="text" id="title" name="title" class="form-control" required maxlength="255" placeholder="e.g. Marina View 2BR — Full Walkthrough">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"></textarea>
        </div>

        <?php require __DIR__ . '/_options.php'; ?>

        <p class="muted mb-2">The tour starts as a draft. Add scenes, then publish it from the edit page.</p>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Create Tour &amp; Add Scenes</button>
            <a href="<?php echo base_url('?route=tour'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
