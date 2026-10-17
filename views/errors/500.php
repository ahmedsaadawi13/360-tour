<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something went wrong</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card text-center">
            <h1 style="font-size: 4rem; color: var(--danger-color);">500</h1>
            <h2>Something went wrong</h2>
            <p>We have been notified. Please try again in a moment.</p>
            <a href="<?php echo base_url(); ?>" class="btn btn-primary mt-2">Go Home</a>
        </div>
    </div>
</body>
</html>
