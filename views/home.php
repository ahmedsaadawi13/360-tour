<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(config('name')); ?> - 360° Virtual Tours for Real Estate</title>
    <meta name="description" content="Create immersive 360° virtual tours for your real estate listings, capture leads and share them anywhere.">
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="hero">
        <h1>360° Virtual Tours for Real Estate</h1>
        <p>Upload panoramas, link rooms with hotspots, and turn viewers into leads.</p>
        <div>
            <a href="<?php echo base_url('?route=auth/register'); ?>" class="btn btn-success">Start free <?php echo (int) config('trial_days'); ?>-day trial</a>
            <a href="<?php echo base_url('?route=auth/login'); ?>" class="btn btn-primary">Login</a>
        </div>
    </div>

    <section class="public-section">
        <div class="feature-grid">
            <div class="card"><h3>Immersive viewer</h3><p>True spherical WebGL projection with zoom, fullscreen, auto-rotate and mobile touch support.</p></div>
            <div class="card"><h3>Visual hotspot editor</h3><p>Click on the panorama to place navigation, info and link hotspots.</p></div>
            <div class="card"><h3>Lead capture</h3><p>Visitors contact you from inside the tour; leads go to your inbox, CRM or n8n via webhooks.</p></div>
            <div class="card"><h3>Share &amp; embed</h3><p>Branded public links, iframe embeds for your website and portals, and view analytics.</p></div>
        </div>
    </section>

    <?php if (!empty($plans)): ?>
        <section class="public-section">
            <h2 class="text-center mb-3">Plans</h2>
            <div class="feature-grid">
                <?php foreach ($plans as $plan): ?>
                    <div class="plan-card" style="background: var(--white);">
                        <h3><?php echo e($plan['name']); ?></h3>
                        <p class="muted"><?php echo e($plan['description']); ?></p>
                        <div class="plan-price"><?php echo format_currency($plan['price_monthly']); ?><small>/mo</small></div>
                        <ul>
                            <?php foreach ((new Plan())->getFeaturesArray($plan) as $feature): ?>
                                <li>✓ <?php echo e($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo base_url('?route=auth/register&plan=' . urlencode($plan['slug'])); ?>" class="btn btn-primary">Start trial</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</body>
</html>
