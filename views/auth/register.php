<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Splash360 Tour</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 600px;">
            <h2>Create Your Account</h2>

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo e(old('company_name')); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo e(old('first_name')); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo e(old('last_name')); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <small class="muted">At least 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Plan</label>
                    <select name="plan_id" class="form-select" required>
                        <option value="">Choose a plan...</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo (int) $plan['id']; ?>" <?php echo ((string) ($selectedPlan ?? '') === (string) $plan['id'] || ($selectedPlan ?? '') === $plan['slug']) ? 'selected' : ''; ?>>
                                <?php echo e($plan['name']); ?> -
                                <?php echo format_currency($plan['price_monthly']); ?>/month
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="muted mb-2"><?php echo (int) config('trial_days'); ?>-day free trial. No credit card required.</p>
                <button type="submit" class="btn btn-success" style="width: 100%;">Create Account</button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="<?php echo base_url('?route=auth/login'); ?>">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
