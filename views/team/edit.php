<?php
$pageTitle = 'Edit Team Member';
ob_start();
?>

<h2 class="mb-3"><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></h2>

<div class="card" style="max-width: 640px;">
    <form method="POST">
        <?php echo csrf_field(); ?>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="first_name">First Name</label>
                <input id="first_name" name="first_name" class="form-control" value="<?php echo e($member['first_name']); ?>" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label" for="last_name">Last Name</label>
                <input id="last_name" name="last_name" class="form-control" value="<?php echo e($member['last_name']); ?>" maxlength="100">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?php echo e($member['email']); ?>" disabled>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="agent" <?php echo $member['role'] === 'agent' ? 'selected' : ''; ?>>Agent</option>
                    <option value="tenant_admin" <?php echo $member['role'] === 'tenant_admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="active" <?php echo $member['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $member['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (cannot log in)</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="<?php echo base_url('?route=team'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    <?php if (!$member['last_login']): ?>
        <hr class="mt-2 mb-2">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="resend_invite">
            <button type="submit" class="btn btn-secondary">Resend invitation email</button>
        </form>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
