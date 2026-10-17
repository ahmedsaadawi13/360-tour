<?php
$pageTitle = 'My Profile';
ob_start();
?>

<h2 class="mb-3">My Profile</h2>

<div class="grid-2">
    <div class="card">
        <h3 class="mb-2">Personal details</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="profile">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input id="first_name" name="first_name" class="form-control" value="<?php echo e($profile['first_name']); ?>" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input id="last_name" name="last_name" class="form-control" value="<?php echo e($profile['last_name']); ?>" required maxlength="100">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-control" value="<?php echo e($profile['email']); ?>" disabled>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>

    <div class="card">
        <h3 class="mb-2">Change password</h3>
        <form method="POST" autocomplete="off">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="password">
            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input id="current_password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input id="new_password" type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="new_password_confirm">Confirm New Password</label>
                <input id="new_password_confirm" type="password" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
