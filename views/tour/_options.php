<?php /* Shared tour checkboxes; expects $tour (may be empty for create) */ ?>
<div class="form-group">
    <label><input type="checkbox" name="is_public" value="1" <?php echo ($tour['is_public'] ?? 1) ? 'checked' : ''; ?>>
        Publicly accessible (anyone with the link)</label>
</div>
<div class="form-group">
    <label><input type="checkbox" name="lead_form_enabled" value="1" <?php echo ($tour['lead_form_enabled'] ?? 1) ? 'checked' : ''; ?>>
        Show "Contact agent" lead form</label>
</div>
<div class="form-group">
    <label><input type="checkbox" name="auto_rotate" value="1" <?php echo !empty($tour['auto_rotate']) ? 'checked' : ''; ?>>
        Auto-rotate the panorama when idle</label>
</div>
<div class="form-group">
    <label><input type="checkbox" name="is_featured" value="1" <?php echo !empty($tour['is_featured']) ? 'checked' : ''; ?>>
        Feature on your public agency page</label>
</div>
