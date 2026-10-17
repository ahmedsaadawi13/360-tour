<?php
$pageTitle = 'Tour Details';
$maxViews = max(1, max(array_column($daily, 'views')));
$publicUrl = tour_url($tour['slug']);
$embedCode = '<iframe src="' . tour_embed_url($tour['slug']) . '" width="100%" height="500" style="border:0" allowfullscreen loading="lazy"></iframe>';
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($tour['title']); ?></h2>
    <div class="flex gap-1">
        <a href="<?php echo base_url('?route=tour/edit&id=' . $tour['id']); ?>" class="btn btn-secondary">Edit</a>
        <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>" class="btn btn-primary">+ Add Scene</a>
        <a href="<?php echo e($publicUrl); ?>" class="btn btn-success" target="_blank"><?php echo $tour['status'] === 'published' ? 'View Tour' : 'Preview'; ?></a>
    </div>
</div>

<?php if ($tour['status'] !== 'published'): ?>
    <div class="alert alert-info">
        This tour is a draft. <?php echo $scenes ? 'Edit the tour and set its status to Published to share it.' : 'Add a scene, then publish it.'; ?>
    </div>
<?php endif; ?>

<div class="grid-2-1">
    <div class="card">
        <h3 class="mb-2">Views — last 30 days</h3>
        <div class="kpis">
            <div><strong><?php echo number_format($totals['views']); ?></strong><span class="muted">Total views</span></div>
            <div><strong><?php echo number_format($totals['visitors']); ?></strong><span class="muted">Unique visitors</span></div>
            <div><strong><?php echo number_format($totals['embed_views']); ?></strong><span class="muted">Embedded views</span></div>
            <div><strong><?php echo number_format($leadsCount); ?></strong><span class="muted"><a href="<?php echo base_url('?route=lead&tour_id=' . $tour['id']); ?>">Leads</a></span></div>
        </div>
        <div class="bar-chart" role="img" aria-label="Daily views for the last 30 days">
            <?php foreach ($daily as $day): ?>
                <div class="bar" style="height: <?php echo round($day['views'] / $maxViews * 100, 1); ?>%"
                     title="<?php echo e(format_date($day['date'], 'M j') . ': ' . $day['views'] . ' views, ' . $day['visitors'] . ' visitors'); ?>"></div>
            <?php endforeach; ?>
        </div>
        <div class="bar-chart-axis">
            <span><?php echo e(format_date($daily[0]['date'], 'M j')); ?></span>
            <span>Today</span>
        </div>
        <details class="mt-1">
            <summary class="muted">Show data table</summary>
            <table class="table">
                <thead><tr><th>Date</th><th>Views</th><th>Visitors</th></tr></thead>
                <tbody>
                    <?php foreach (array_reverse($daily) as $day): ?>
                        <tr><td><?php echo e($day['date']); ?></td><td><?php echo (int) $day['views']; ?></td><td><?php echo (int) $day['visitors']; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <?php if ($referrers): ?>
            <h4 class="mt-2">Top referrers</h4>
            <table class="table">
                <?php foreach ($referrers as $ref): ?>
                    <tr><td><?php echo e($ref['referrer_host']); ?></td><td class="text-right"><?php echo (int) $ref['views']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <h3 class="mb-2">Tour Information</h3>
            <p><strong>Property:</strong> <a href="<?php echo base_url('?route=property/view&id=' . $property['id']); ?>"><?php echo e($property['title']); ?></a></p>
            <p><strong>Status:</strong> <span class="badge <?php echo $tour['status'] === 'published' ? 'badge-success' : 'badge-warning'; ?>"><?php echo e($tour['status']); ?></span>
                <?php if (!$tour['is_public']): ?><span class="badge badge-danger">private</span><?php endif; ?></p>
            <p><strong>Lead form:</strong> <?php echo $tour['lead_form_enabled'] ? 'Enabled' : 'Disabled'; ?></p>
            <?php if ($tour['description']): ?>
                <p><strong>Description:</strong><br><?php echo nl2br(e($tour['description'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="mb-2">Share</h3>
            <label class="form-label" for="publicUrl">Public link</label>
            <div class="copy-field mb-2">
                <input id="publicUrl" class="form-control" readonly value="<?php echo e($publicUrl); ?>">
                <button type="button" class="btn btn-sm btn-secondary" data-copy="publicUrl">Copy</button>
            </div>
            <label class="form-label" for="embedCode">Embed on your website</label>
            <div class="copy-field mb-2">
                <input id="embedCode" class="form-control" readonly value="<?php echo e($embedCode); ?>">
                <button type="button" class="btn btn-sm btn-secondary" data-copy="embedCode">Copy</button>
            </div>
            <div class="flex gap-1">
                <a class="btn btn-sm btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?php echo rawurlencode($tour['title'] . ' ' . $publicUrl); ?>">WhatsApp</a>
                <a class="btn btn-sm btn-secondary" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($publicUrl); ?>">Facebook</a>
                <a class="btn btn-sm btn-secondary" href="mailto:?subject=<?php echo rawurlencode($tour['title']); ?>&body=<?php echo rawurlencode($publicUrl); ?>">Email</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="flex-between mb-2">
        <h3>Scenes (<?php echo count($scenes); ?>)</h3>
        <a href="<?php echo base_url('?route=scene&tour_id=' . $tour['id']); ?>" class="btn btn-sm btn-secondary">Reorder / manage</a>
    </div>
    <?php if (!empty($scenes)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($scenes as $scene): ?>
                <div class="scene-card">
                    <img src="<?php echo e(asset($scene['thumbnail_path'] ?: $scene['image_path'])); ?>" alt="" loading="lazy">
                    <h4 style="font-size: 14px;"><?php echo e($scene['name']); ?></h4>
                    <a href="<?php echo base_url('?route=scene/edit&id=' . $scene['id']); ?>" class="btn btn-sm btn-secondary" style="width: 100%; margin-top: 10px;">Edit Scene &amp; Hotspots</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No scenes yet. <a href="<?php echo base_url('?route=scene/create&tour_id=' . $tour['id']); ?>">Add your first scene</a></p>
    <?php endif; ?>
</div>

<div class="card">
    <?php echo post_button(base_url('?route=tour/delete'), 'Delete tour', 'btn btn-danger', 'Delete this tour and all its scenes?', ['id' => $tour['id']]); ?>
</div>

<?php
$content = ob_get_clean();
$scripts = '<script>document.querySelectorAll("[data-copy]").forEach(function(b){b.addEventListener("click",function(){var i=document.getElementById(b.dataset.copy);i.select();(navigator.clipboard?navigator.clipboard.writeText(i.value):Promise.resolve(document.execCommand("copy"))).then(function(){b.textContent="Copied!";setTimeout(function(){b.textContent="Copy"},1500)})})});</script>';
require __DIR__ . '/../layouts/main.php';
?>
