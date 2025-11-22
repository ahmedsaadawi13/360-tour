<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Splash360 Tour</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>Set New Password</h2>

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirm" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Reset Password</button>
            </form>

            <div class="auth-links">
                <p><a href="<?php echo base_url('?route=auth/login'); ?>">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
