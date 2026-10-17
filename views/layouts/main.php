<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(($pageTitle ?? 'Dashboard') . ' - ' . config('name')); ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <?php if (!empty($useViewer)): ?>
        <link rel="stylesheet" href="<?php echo asset('css/viewer360.css'); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="main-content">
            <?php require __DIR__ . '/../partials/topbar.php'; ?>

            <div class="container">
                <?php require __DIR__ . '/../partials/flash.php'; ?>
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($useViewer)): ?>
        <script src="<?php echo asset('js/viewer360.js'); ?>"></script>
    <?php endif; ?>
    <?php echo $scripts ?? ''; ?>
</body>
</html>
