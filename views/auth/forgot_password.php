<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Splash360 Tour</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>Reset Password</h2>

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
            </form>

            <div class="auth-links">
                <p><a href="<?php echo base_url('?route=auth/login'); ?>">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
