<?php
$pageTitle = 'Invite Team Member';
ob_start();
?>

<h2 class="mb-3">Invite Team Member</h2>

<div class="card" style="max-width: 640px;">
    <form method="POST">
        <?php echo csrf_field(); ?>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="first_name">First Name *</label>
                <input id="first_name" name="first_name" class="form-control" value="<?php echo e(old('first_name')); ?>" required maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label" for="last_name">Last Name *</label>
                <input id="last_name" name="last_name" class="form-control" value="<?php echo e(old('last_name')); ?>" required maxlength="100">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="email">Email *</label>
            <input id="email" type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="role">Role</label>
            <select id="role" name="role" class="form-select">
                <option value="agent" <?php echo old('role', 'agent') === 'agent' ? 'selected' : ''; ?>>Agent</option>
                <option value="tenant_admin" <?php echo old('role') === 'tenant_admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <p class="muted mb-2">They will receive an email with a link to set their password.</p>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Send Invitation</button>
            <a href="<?php echo base_url('?route=team'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
