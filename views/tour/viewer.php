<?php
$propertyFacts = array_filter([
    $tour['city'] ? $tour['city'] . ($tour['area'] ? ', ' . $tour['area'] : '') : null,
    $tour['bedrooms'] ? $tour['bedrooms'] . ' bed' : null,
    $tour['bathrooms'] ? $tour['bathrooms'] . ' bath' : null,
    $tour['size'] ? rtrim(rtrim($tour['size'], '0'), '.') . ' m²' : null,
]);
$showLeadForm = $tour['lead_form_enabled'] && !$isPreview;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e($tour['title']); ?> - Virtual Tour</title>
    <meta name="description" content="<?php echo e(mb_substr($tour['description'] ?: $tour['property_title'], 0, 160)); ?>">
    <meta property="og:title" content="<?php echo e($tour['title']); ?>">
    <meta property="og:description" content="<?php echo e('360° virtual tour of ' . $tour['property_title']); ?>">
    <?php if ($scenes): ?>
        <meta property="og:image" content="<?php echo e($scenes[0]['thumb']); ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?php echo e(tour_url($tour['slug'])); ?>">
    <?php if ($isPreview): ?>
        <meta name="robots" content="noindex">
    <?php endif; ?>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/viewer360.css'); ?>">
    <style>
        :root { --v360-accent: <?php echo e($brandColor); ?>; }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; background: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #fff; }
        #viewer360 { position: fixed; inset: 0; }
        .tour-header { position: fixed; top: 0; left: 0; right: 0; z-index: 10; display: flex;
            justify-content: space-between; align-items: center; gap: 15px; padding: 12px 20px;
            background: linear-gradient(rgba(0,0,0,.75), rgba(0,0,0,0)); pointer-events: none; }
        .tour-header > * { pointer-events: auto; }
        .tour-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .tour-brand img { height: 40px; max-width: 120px; object-fit: contain; background: #fff; border-radius: 4px; padding: 2px; }
        .tour-brand h1 { margin: 0; font-size: 1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tour-facts { font-size: .85rem; opacity: .85; }
        .tour-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .t-btn { border: 0; border-radius: 20px; padding: 8px 16px; font-size: .9rem; cursor: pointer;
            background: rgba(0,0,0,.6); color: #fff; }
        .t-btn-primary { background: var(--v360-accent); }
        .t-btn:hover { filter: brightness(1.1); }
        .scene-strip { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10; display: flex; gap: 10px;
            padding: 12px 20px calc(12px + env(safe-area-inset-bottom)); overflow-x: auto;
            background: linear-gradient(rgba(0,0,0,0), rgba(0,0,0,.8)); }
        .scene-item { flex: 0 0 auto; width: 110px; text-align: center; cursor: pointer; border: 2px solid transparent;
            border-radius: 8px; padding: 4px; background: rgba(0,0,0,.35); color: #fff; font-size: .8rem; }
        .scene-item.active, .scene-item:hover { border-color: var(--v360-accent); }
        .scene-item img { width: 100%; height: 55px; object-fit: cover; border-radius: 5px; display: block; margin-bottom: 4px; }
        .scene-item span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 50; display: none;
            align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; color: #2c3e50; border-radius: 10px; padding: 25px; width: 100%; max-width: 460px;
            max-height: 90vh; overflow-y: auto; position: relative; }
        .modal h2 { margin-top: 0; }
        .modal-close { position: absolute; top: 10px; right: 14px; border: 0; background: none; font-size: 1.6rem; cursor: pointer; color: #7f8c8d; }
        .modal label { display: block; font-weight: 600; margin: 10px 0 4px; font-size: .9rem; }
        .modal input, .modal textarea { width: 100%; padding: 9px 10px; border: 1px solid #dfe6e9; border-radius: 5px; font: inherit; }
        .modal textarea { min-height: 90px; resize: vertical; }
        .modal .t-btn { margin-top: 15px; width: 100%; padding: 11px; }
        .form-msg { margin-top: 10px; font-size: .9rem; }
        .form-msg.error { color: #c0392b; }
        .form-msg.ok { color: #27ae60; }
        .hp { position: absolute; left: -10000px; }
        .preview-banner { position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 20;
            background: #f39c12; color: #000; font-size: .8rem; padding: 3px 12px; border-radius: 0 0 6px 6px; }
        .empty { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 20px; }
        .has-strip .v360-controls { bottom: 115px; }
        .powered { position: fixed; left: 12px; bottom: 90px; z-index: 9; font-size: .7rem; opacity: .6; color: #fff; text-decoration: none; }
        @media (max-width: 640px) {
            .tour-facts { display: none; }
            .tour-brand img { height: 30px; }
            .tour-brand h1 { font-size: 1rem; }
            .t-btn { padding: 7px 12px; font-size: .8rem; }
            .scene-item { width: 90px; }
        }
    </style>
</head>
<body class="<?php echo count($scenes) > 1 ? 'has-strip' : ''; ?>">
    <?php if ($isPreview): ?>
        <div class="preview-banner">Preview<?php echo $tour['status'] !== 'published' ? ' (draft)' : ''; ?> — views are not counted</div>
    <?php endif; ?>

    <?php if (!$scenes): ?>
        <div class="empty"><div><h2><?php echo e($tour['title']); ?></h2><p>This tour has no scenes yet.</p></div></div>
    <?php else: ?>
        <div id="viewer360"></div>

        <header class="tour-header">
            <div class="tour-brand">
                <?php if ($tour['tenant_logo'] && !$isEmbed): ?>
                    <img src="<?php echo e(asset($tour['tenant_logo'])); ?>" alt="<?php echo e($tour['tenant_name']); ?>">
                <?php endif; ?>
                <div style="min-width: 0;">
                    <h1><?php echo e($tour['title']); ?></h1>
                    <div class="tour-facts">
                        <?php echo e(implode(' • ', $propertyFacts)); ?>
                        <?php if ($tour['price']): ?>
                            • <?php echo format_currency($tour['price'], $tour['currency']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tour-actions">
                <?php if (!$isEmbed): ?>
                    <button type="button" class="t-btn" id="shareBtn">Share</button>
                <?php endif; ?>
                <?php if ($tour['description'] || $tour['tenant_phone'] || $tour['tenant_email']): ?>
                    <button type="button" class="t-btn" data-open="aboutModal">Details</button>
                <?php endif; ?>
                <?php if ($showLeadForm): ?>
                    <button type="button" class="t-btn t-btn-primary" data-open="leadModal">Contact agent</button>
                <?php endif; ?>
            </div>
        </header>

        <?php if (count($scenes) > 1): ?>
            <nav class="scene-strip" aria-label="Scenes">
                <?php foreach ($scenes as $index => $scene): ?>
                    <button type="button" class="scene-item<?php echo $index === 0 ? ' active' : ''; ?>" data-scene-index="<?php echo $index; ?>">
                        <img src="<?php echo e($scene['thumb']); ?>" alt="" loading="lazy">
                        <span><?php echo e($scene['name']); ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($isEmbed): ?>
            <a class="powered" href="<?php echo e(tour_url($tour['slug'])); ?>" target="_blank" rel="noopener">Open full screen ↗</a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Info hotspot popup -->
    <div class="modal-overlay" id="infoModal" role="dialog" aria-modal="true" aria-labelledby="infoTitle">
        <div class="modal">
            <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
            <h2 id="infoTitle"></h2>
            <p id="infoDescription" style="white-space: pre-line;"></p>
        </div>
    </div>

    <!-- About the property / agency -->
    <div class="modal-overlay" id="aboutModal" role="dialog" aria-modal="true" aria-labelledby="aboutTitle">
        <div class="modal">
            <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
            <h2 id="aboutTitle"><?php echo e($tour['property_title']); ?></h2>
            <?php if ($tour['description']): ?>
                <p style="white-space: pre-line;"><?php echo e($tour['description']); ?></p>
            <?php endif; ?>
            <p><strong><?php echo e($tour['tenant_name']); ?></strong><br>
                <?php if ($tour['tenant_phone']): ?>
                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $tour['tenant_phone'])); ?>"><?php echo e($tour['tenant_phone']); ?></a><br>
                <?php endif; ?>
                <?php if ($tour['tenant_email']): ?>
                    <a href="mailto:<?php echo e($tour['tenant_email']); ?>"><?php echo e($tour['tenant_email']); ?></a><br>
                <?php endif; ?>
                <?php if (is_safe_url($tour['tenant_website'])): ?>
                    <a href="<?php echo e($tour['tenant_website']); ?>" target="_blank" rel="noopener"><?php echo e($tour['tenant_website']); ?></a><br>
                <?php endif; ?>
                <?php if (!$isEmbed): ?>
                    <a href="<?php echo e(base_url('agency/' . $tour['tenant_slug'])); ?>">More tours from this agency</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($showLeadForm): ?>
        <!-- Lead capture -->
        <div class="modal-overlay" id="leadModal" role="dialog" aria-modal="true" aria-labelledby="leadTitle">
            <div class="modal">
                <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
                <h2 id="leadTitle">Interested in this property?</h2>
                <p style="margin-top: -8px; color: #7f8c8d;">Leave your details and <?php echo e($tour['tenant_name']); ?> will get back to you.</p>
                <form id="leadForm" novalidate>
                    <input type="hidden" name="tour" value="<?php echo e($tour['slug']); ?>">
                    <input type="hidden" name="embed" value="<?php echo $isEmbed ? 1 : 0; ?>">
                    <div class="hp" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <label for="leadName">Name *</label>
                    <input id="leadName" name="name" required maxlength="150" autocomplete="name">
                    <label for="leadEmail">Email *</label>
                    <input id="leadEmail" name="email" type="email" required maxlength="255" autocomplete="email">
                    <label for="leadPhone">Phone</label>
                    <input id="leadPhone" name="phone" type="tel" maxlength="50" autocomplete="tel">
                    <label for="leadMessage">Message</label>
                    <textarea id="leadMessage" name="message" maxlength="2000">I would like more information about <?php echo e($tour['property_title']); ?>.</textarea>
                    <button type="submit" class="t-btn t-btn-primary">Send enquiry</button>
                    <div class="form-msg" id="leadMsg" role="status"></div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($scenes): ?>
    <script src="<?php echo asset('js/viewer360.js'); ?>"></script>
    <script>
    (function () {
        var scenes = <?php echo js_encode($scenes); ?>;
        var tourUrl = <?php echo js_encode(tour_url($tour['slug'])); ?>;
        var leadEndpoint = <?php echo js_encode(base_url('?route=lead/submit')); ?>;
        var current = 0;

        var viewer = new Viewer360('viewer360', {
            autoRotate: <?php echo $tour['auto_rotate'] ? 'true' : 'false'; ?>,
            onHotspotClick: function (h) {
                if (h.type === 'navigation') {
                    var target = scenes.findIndex(function (s) { return s.id === h.target; });
                    if (target !== -1) showScene(target);
                } else if (h.type === 'info') {
                    document.getElementById('infoTitle').textContent = h.label || '';
                    document.getElementById('infoDescription').textContent = h.description || '';
                    openModal('infoModal');
                } else if (h.type === 'link' && h.url) {
                    window.open(h.url, '_blank', 'noopener');
                }
            }
        });

        function showScene(index) {
            current = index;
            var s = scenes[index];
            viewer.loadPanorama(s.image, s.yaw, s.pitch, s.hotspots).catch(function (e) { console.error(e); });
            document.querySelectorAll('.scene-item').forEach(function (el) {
                el.classList.toggle('active', +el.dataset.sceneIndex === index);
            });
            if (history.replaceState && !<?php echo $isEmbed ? 'true' : 'false'; ?>) {
                history.replaceState(null, '', '#scene-' + s.id);
            }
        }

        // Deep link: #scene-{id}
        var match = location.hash.match(/^#scene-(\d+)$/);
        var start = match ? scenes.findIndex(function (s) { return s.id === +match[1]; }) : 0;
        showScene(start > -1 ? start : 0);

        document.querySelectorAll('.scene-item').forEach(function (el) {
            el.addEventListener('click', function () { showScene(+el.dataset.sceneIndex); });
        });

        // Modals
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) { m.classList.add('active'); var f = m.querySelector('input:not([type=hidden]):not([tabindex="-1"]), button'); f && f.focus(); }
        }
        function closeModals() {
            document.querySelectorAll('.modal-overlay.active').forEach(function (m) { m.classList.remove('active'); });
        }
        document.querySelectorAll('[data-open]').forEach(function (b) {
            b.addEventListener('click', function () { openModal(b.dataset.open); });
        });
        document.querySelectorAll('.modal-overlay').forEach(function (m) {
            m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-close')) closeModals(); });
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModals(); });

        // Share
        var shareBtn = document.getElementById('shareBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: document.title, url: tourUrl }).catch(function () {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(tourUrl).then(function () {
                        shareBtn.textContent = 'Link copied!';
                        setTimeout(function () { shareBtn.textContent = 'Share'; }, 2000);
                    });
                } else {
                    prompt('Copy this link:', tourUrl);
                }
            });
        }

        // Lead form
        var form = document.getElementById('leadForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var msg = document.getElementById('leadMsg');
                var btn = form.querySelector('button[type=submit]');
                btn.disabled = true;
                msg.className = 'form-msg';
                msg.textContent = 'Sending…';
                fetch(leadEndpoint, { method: 'POST', body: new FormData(form), credentials: 'omit' })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        msg.className = 'form-msg ' + (res.success ? 'ok' : 'error');
                        msg.textContent = res.message;
                        if (res.success) form.reset(); else btn.disabled = false;
                    })
                    .catch(function () {
                        msg.className = 'form-msg error';
                        msg.textContent = 'Could not send. Please try again.';
                        btn.disabled = false;
                    });
            });
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>
