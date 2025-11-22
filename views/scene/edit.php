<?php
$pageTitle = 'Edit Scene';
ob_start();
?>

<h2 class="mb-3">Edit Scene: <?php echo e($scene['name']); ?></h2>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <h3>Scene Settings</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="form-group">
                <label class="form-label">Scene Name *</label>
                <input type="text" name="name" class="form-control" value="<?php echo e($scene['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Current Image</label>
                <?php if ($scene['image_path']): ?>
                    <img src="<?php echo asset($scene['image_path']); ?>" alt="Scene" style="width: 100%; border-radius: 5px; margin-bottom: 10px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Replace Image</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/jpg,image/png">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Initial Yaw</label>
                    <input type="number" step="0.01" name="initial_yaw" class="form-control" value="<?php echo $scene['initial_yaw']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Pitch</label>
                    <input type="number" step="0.01" name="initial_pitch" class="form-control" value="<?php echo $scene['initial_pitch']; ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Scene</button>
        </form>
    </div>

    <div class="card">
        <div class="flex-between mb-2">
            <h3>Hotspots</h3>
            <button onclick="toggleHotspotForm()" class="btn btn-sm btn-primary">+ Add Hotspot</button>
        </div>

        <div id="hotspot-form" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <form method="POST" action="<?php echo base_url('?route=hotspot/create'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="scene_id" value="<?php echo $scene['id']; ?>">

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="toggleHotspotFields(this.value)">
                        <option value="navigation">Navigation (Go to another scene)</option>
                        <option value="info">Information Point</option>
                        <option value="link">External Link</option>
                    </select>
                </div>

                <div id="target-scene-field" class="form-group">
                    <label class="form-label">Target Scene</label>
                    <select name="target_scene_id" class="form-select">
                        <option value="">Select scene...</option>
                        <?php foreach ($availableScenes as $s): ?>
                            <?php if ($s['id'] != $scene['id']): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="url-field" class="form-group" style="display: none;">
                    <label class="form-label">External URL</label>
                    <input type="url" name="external_url" class="form-control">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Yaw (Horizontal)</label>
                        <input type="number" step="0.01" name="yaw" class="form-control" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pitch (Vertical)</label>
                        <input type="number" step="0.01" name="pitch" class="form-control" value="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-success btn-sm">Add Hotspot</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($hotspots)): ?>
                    <?php foreach ($hotspots as $hotspot): ?>
                        <tr>
                            <td><?php echo e($hotspot['label']); ?></td>
                            <td><span class="badge badge-info"><?php echo e($hotspot['type']); ?></span></td>
                            <td>
                                <a href="<?php echo base_url('?route=hotspot/delete&id=' . $hotspot['id'] . '&csrf_token=' . csrf_token()); ?>" onclick="return confirm('Delete this hotspot?')" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No hotspots yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleHotspotForm() {
    const form = document.getElementById('hotspot-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleHotspotFields(type) {
    document.getElementById('target-scene-field').style.display = type === 'navigation' ? 'block' : 'none';
    document.getElementById('url-field').style.display = type === 'link' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
