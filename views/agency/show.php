<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tenant['name']); ?> - Virtual Tours</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <?php if (is_hex_color($tenant['brand_color'])): ?>
        <style>:root { --primary-color: <?php echo e($tenant['brand_color']); ?>; }</style>
    <?php endif; ?>
</head>
<body>
    <header class="public-header">
        <div class="inner flex-between">
            <div class="flex gap-2" style="align-items: center;">
                <?php if ($tenant['logo']): ?>
                    <img src="<?php echo e(asset($tenant['logo'])); ?>" alt="" style="height: 56px; background: #fff; border-radius: 6px; padding: 4px;">
                <?php endif; ?>
                <div>
                    <h1><?php echo e($tenant['name']); ?></h1>
                    <div style="opacity: .8;"><?php echo count($tours); ?> virtual tour<?php echo count($tours) === 1 ? '' : 's'; ?></div>
                </div>
            </div>
            <div class="text-right" style="opacity: .9;">
                <?php if ($tenant['phone']): ?><div><?php echo e($tenant['phone']); ?></div><?php endif; ?>
                <div><a href="mailto:<?php echo e($tenant['email']); ?>" style="color: #fff;"><?php echo e($tenant['email']); ?></a></div>
                <?php if (is_safe_url($tenant['website'])): ?>
                    <div><a href="<?php echo e($tenant['website']); ?>" style="color: #fff;" rel="noopener" target="_blank">Website</a></div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="public-section">
        <?php if ($tours): ?>
            <div class="tour-grid">
                <?php foreach ($tours as $tour): ?>
                    <a class="tour-tile" href="<?php echo e(tour_url($tour['slug'])); ?>">
                        <?php $cover = $tour['cover_image'] ?: $tour['main_image']; ?>
                        <?php if ($cover): ?>
                            <img src="<?php echo e(asset($cover)); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <div class="placeholder"></div>
                        <?php endif; ?>
                        <div class="body">
                            <h3><?php echo e($tour['title']); ?></h3>
                            <div class="muted"><?php echo e(implode(' • ', array_filter([humanize($tour['property_type']), $tour['city'], $tour['area']]))); ?></div>
                            <?php if ($tour['price'] !== null): ?>
                                <strong><?php echo format_currency($tour['price'], $tour['currency']); ?></strong>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">No public tours yet.</p>
        <?php endif; ?>
    </main>
</body>
</html>
