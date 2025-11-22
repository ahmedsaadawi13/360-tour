<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Splash360 Tour</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>Login to Your Account</h2>

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>

            <div class="auth-links">
                <p><a href="<?php echo base_url('?route=auth/forgot-password'); ?>">Forgot Password?</a></p>
                <p>Don't have an account? <a href="<?php echo base_url('?route=auth/register'); ?>">Register</a></p>
            </div>
        </div>
    </div>
</body>
</html>
