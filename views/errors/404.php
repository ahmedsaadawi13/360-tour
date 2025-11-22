<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card text-center">
            <h1 style="font-size: 4rem; color: var(--primary-color);">404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for does not exist.</p>
            <a href="<?php echo base_url(); ?>" class="btn btn-primary mt-2">Go Home</a>
        </div>
    </div>
</body>
</html>
