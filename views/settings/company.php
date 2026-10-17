<?php
$pageTitle = 'Company Settings';
ob_start();
?>

<h2 class="mb-3">Branding &amp; Settings</h2>

<div class="grid-2-1">
    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <h3 class="mb-2">Company profile</h3>
            <div class="form-group">
                <label class="form-label" for="name">Company Name *</label>
                <input id="name" name="name" class="form-control" value="<?php echo e($tenant['name']); ?>" required maxlength="255">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="email">Contact Email *</label>
                    <input id="email" type="email" name="email" class="form-control" value="<?php echo e($tenant['email']); ?>" required>
                    <small class="muted">Receives lead notifications and invoices.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input id="phone" name="phone" class="form-control" value="<?php echo e($tenant['phone']); ?>" maxlength="50">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="website">Website</label>
                <input id="website" type="url" name="website" class="form-control" value="<?php echo e($tenant['website']); ?>" placeholder="https://">
            </div>
            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="2"><?php echo e($tenant['address']); ?></textarea>
            </div>

            <h3 class="mb-2 mt-2">Tour branding</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="logo">Logo</label>
                    <?php if ($tenant['logo']): ?>
                        <div class="mb-1"><img src="<?php echo e(asset($tenant['logo'])); ?>" alt="Logo" style="max-height: 60px;"></div>
                        <label class="muted"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
                    <?php endif; ?>
                    <input id="logo" type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group">
                    <label class="form-label" for="brand_color">Accent color</label>
                    <input id="brand_color" type="color" name="brand_color" class="form-control" style="height: 42px;" value="<?php echo e(is_hex_color($tenant['brand_color']) ? $tenant['brand_color'] : '#3498db'); ?>">
                    <small class="muted">Buttons and hotspots in your public tours.</small>
                </div>
            </div>

            <h3 class="mb-2 mt-2">Integrations</h3>
            <div class="form-group">
                <label class="form-label" for="webhook_url">Webhook URL</label>
                <input id="webhook_url" type="url" name="webhook_url" class="form-control" value="<?php echo e($tenant['webhook_url']); ?>" placeholder="https://your-n8n.example.com/webhook/...">
                <small class="muted">We POST JSON for <code>lead.created</code> and <code>tour.published</code> events — connect n8n, Zapier or your CRM.
                    Requests are signed with an <code>X-Splash360-Signature</code> HMAC-SHA256 header.</small>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>

    <div>
        <div class="card">
            <h3 class="mb-2">Public agency page</h3>
            <p class="muted">All your published public tours in one place:</p>
            <p><a href="<?php echo e(base_url('agency/' . $tenant['slug'])); ?>" target="_blank"><?php echo e(base_url('agency/' . $tenant['slug'])); ?></a></p>
        </div>
        <?php if ($tenant['webhook_url']): ?>
            <div class="card">
                <h3 class="mb-2">Test webhook</h3>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="test_webhook">
                    <button type="submit" class="btn btn-secondary">Send test event</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
