<?php
$pageTitle = 'Edit Scene';
$useViewer = true;
$otherScenes = array_values(array_filter($availableScenes, function ($s) use ($scene) {
    return $s['id'] != $scene['id'];
}));
ob_start();
?>

<div class="flex-between mb-2">
    <h2>Scene: <?php echo e($scene['name']); ?></h2>
    <div class="flex gap-1">
        <a href="<?php echo base_url('?route=scene&tour_id=' . $tour['id']); ?>" class="btn btn-secondary">← All scenes</a>
        <a href="<?php echo e(tour_url($tour['slug']) . '#scene-' . $scene['id']); ?>" target="_blank" class="btn btn-success">Preview tour</a>
    </div>
</div>

<div class="card">
    <div id="editorViewer" class="editor-viewer"></div>
    <div class="editor-toolbar">
        <span class="muted">Drag to look around. <strong>Click on the panorama</strong> to place a hotspot.</span>
        <span>View: <code id="currentView">yaw 0, pitch 0</code></span>
        <button type="button" class="btn btn-sm btn-primary" id="setInitialView">Use current view as starting view</button>
        <span id="setViewMsg" class="muted" role="status"></span>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 class="mb-2">Scene Settings</h3>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label" for="sceneName">Scene Name *</label>
                <input type="text" id="sceneName" name="name" class="form-control" value="<?php echo e($scene['name']); ?>" required maxlength="255">
            </div>

            <div class="form-group">
                <label class="form-label" for="sceneImage">Replace 360° Image</label>
                <input type="file" id="sceneImage" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small class="muted">Replacing keeps hotspots; their positions are angles, so check them afterwards.</small>
            </div>

            <div class="grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="initialYaw">Starting Yaw</label>
                    <input type="number" step="0.01" min="-180" max="180" id="initialYaw" name="initial_yaw" class="form-control" value="<?php echo e($scene['initial_yaw']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="initialPitch">Starting Pitch</label>
                    <input type="number" step="0.01" min="-90" max="90" id="initialPitch" name="initial_pitch" class="form-control" value="<?php echo e($scene['initial_pitch']); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Scene</button>
        </form>
    </div>

    <div class="card">
        <div class="flex-between mb-2">
            <h3>Hotspots (<?php echo count($hotspots); ?>)</h3>
            <button type="button" id="newHotspotBtn" class="btn btn-sm btn-primary">+ Add Hotspot</button>
        </div>

        <div id="hotspotForm" class="hotspot-form" hidden>
            <h4 id="hotspotFormTitle" class="mb-1">New hotspot</h4>
            <form method="POST" id="hotspotFormEl" action="<?php echo base_url('?route=hotspot/create'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="scene_id" value="<?php echo (int) $scene['id']; ?>">
                <input type="hidden" name="id" id="hotspotId" value="">

                <div class="form-group">
                    <label class="form-label" for="hotspotType">Type</label>
                    <select name="type" id="hotspotType" class="form-select">
                        <option value="navigation">Navigation (go to another scene)</option>
                        <option value="info">Information point</option>
                        <option value="link">External link</option>
                    </select>
                </div>

                <div id="targetSceneField" class="form-group">
                    <label class="form-label" for="targetScene">Target Scene</label>
                    <?php if ($otherScenes): ?>
                        <select name="target_scene_id" id="targetScene" class="form-select">
                            <?php foreach ($otherScenes as $s): ?>
                                <option value="<?php echo (int) $s['id']; ?>"><?php echo e($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="muted">Add another scene to this tour to create navigation hotspots.</p>
                    <?php endif; ?>
                </div>

                <div id="urlField" class="form-group" hidden>
                    <label class="form-label" for="externalUrl">External URL</label>
                    <input type="url" name="external_url" id="externalUrl" class="form-control" placeholder="https://">
                </div>

                <div class="grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" for="hotspotYaw">Yaw</label>
                        <input type="number" step="0.01" min="-180" max="180" name="yaw" id="hotspotYaw" class="form-control" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hotspotPitch">Pitch</label>
                        <input type="number" step="0.01" min="-90" max="90" name="pitch" id="hotspotPitch" class="form-control" value="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hotspotLabel">Label</label>
                    <input type="text" name="label" id="hotspotLabel" class="form-control" maxlength="255">
                </div>

                <div class="form-group" id="descriptionField">
                    <label class="form-label" for="hotspotDescription">Description</label>
                    <textarea name="description" id="hotspotDescription" class="form-control" rows="3"></textarea>
                </div>

                <div class="flex gap-1">
                    <button type="submit" class="btn btn-success btn-sm" id="hotspotSubmit">Add Hotspot</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="hotspotCancel">Cancel</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Target / Link</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hotspots)): ?>
                        <?php foreach ($hotspots as $hotspot): ?>
                            <tr>
                                <td><?php echo e($hotspot['label'] ?: '—'); ?></td>
                                <td><span class="badge badge-info"><?php echo e($hotspot['type']); ?></span></td>
                                <td class="muted"><?php echo e($hotspot['type'] === 'navigation' ? $hotspot['target_scene_name'] : ($hotspot['type'] === 'link' ? $hotspot['external_url'] : '')); ?></td>
                                <td class="flex gap-1">
                                    <button type="button" class="btn btn-sm btn-secondary" data-edit-hotspot="<?php echo (int) $hotspot['id']; ?>">Edit</button>
                                    <?php echo post_button(base_url('?route=hotspot/delete'), 'Delete', 'btn btn-sm btn-danger', 'Delete this hotspot?', ['id' => $hotspot['id']]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No hotspots yet. Click on the panorama to add one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$hotspotData = array_map(function ($h) {
    return [
        'id' => (int) $h['id'],
        'type' => $h['type'],
        'target' => $h['target_scene_id'] ? (int) $h['target_scene_id'] : null,
        'yaw' => (float) $h['yaw'],
        'pitch' => (float) $h['pitch'],
        'label' => $h['label'],
        'description' => $h['description'],
        'url' => $h['external_url'],
    ];
}, $hotspots);

ob_start();
?>
<script>
(function () {
    var hotspots = <?php echo js_encode($hotspotData); ?>;
    var sceneId = <?php echo (int) $scene['id']; ?>;
    var csrf = <?php echo js_encode(csrf_token()); ?>;
    var urls = {
        create: <?php echo js_encode(base_url('?route=hotspot/create')); ?>,
        update: <?php echo js_encode(base_url('?route=hotspot/update')); ?>,
        setView: <?php echo js_encode(base_url('?route=scene/set-view')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };
    var formBox = $('hotspotForm'), form = $('hotspotFormEl');
    var tempMarker = null;

    var viewer = new Viewer360('editorViewer', {
        onPick: function (yaw, pitch) {
            if (!$('hotspotId').value) {
                openForm(null);
            }
            setPosition(yaw, pitch);
        },
        onHotspotClick: function (h) {
            if (!h.temporary) openForm(h);
        },
        onViewChange: function (v) {
            $('currentView').textContent = 'yaw ' + v.yaw.toFixed(1) + ', pitch ' + v.pitch.toFixed(1);
        }
    });

    viewer.loadPanorama(
        <?php echo js_encode(asset($scene['image_path'])); ?>,
        <?php echo (float) $scene['initial_yaw']; ?>,
        <?php echo (float) $scene['initial_pitch']; ?>,
        hotspots
    );

    function refreshMarkers() {
        var list = hotspots.slice();
        if (tempMarker) list.push(tempMarker);
        viewer.setHotspots(list);
    }

    function setPosition(yaw, pitch) {
        $('hotspotYaw').value = yaw;
        $('hotspotPitch').value = pitch;
        tempMarker = { type: $('hotspotType').value, yaw: yaw, pitch: pitch, label: $('hotspotLabel').value || 'New hotspot', temporary: true };
        refreshMarkers();
    }

    function toggleFields() {
        var type = $('hotspotType').value;
        $('targetSceneField').hidden = type !== 'navigation';
        $('urlField').hidden = type !== 'link';
        $('descriptionField').hidden = type !== 'info';
        $('externalUrl').required = type === 'link';
    }

    function openForm(h) {
        formBox.hidden = false;
        form.reset();
        $('hotspotId').value = h ? h.id : '';
        form.action = h ? urls.update : urls.create;
        $('hotspotFormTitle').textContent = h ? 'Edit hotspot' : 'New hotspot';
        $('hotspotSubmit').textContent = h ? 'Save Hotspot' : 'Add Hotspot';
        if (h) {
            $('hotspotType').value = h.type;
            if (h.target && $('targetScene')) $('targetScene').value = h.target;
            $('externalUrl').value = h.url || '';
            $('hotspotLabel').value = h.label || '';
            $('hotspotDescription').value = h.description || '';
            $('hotspotYaw').value = h.yaw;
            $('hotspotPitch').value = h.pitch;
            viewer.lookAt(h.yaw, h.pitch);
            tempMarker = null;
            refreshMarkers();
        } else {
            var v = viewer.getView();
            setPosition(v.yaw, v.pitch);
        }
        toggleFields();
        $('hotspotLabel').focus({ preventScroll: true });
    }

    function closeForm() {
        formBox.hidden = true;
        $('hotspotId').value = '';
        tempMarker = null;
        refreshMarkers();
    }

    $('hotspotType').addEventListener('change', function () {
        toggleFields();
        if (tempMarker) { tempMarker.type = this.value; refreshMarkers(); }
    });
    $('hotspotLabel').addEventListener('input', function () {
        if (tempMarker) { tempMarker.label = this.value || 'New hotspot'; refreshMarkers(); }
    });
    ['hotspotYaw', 'hotspotPitch'].forEach(function (id) {
        $(id).addEventListener('change', function () {
            setPosition(parseFloat($('hotspotYaw').value) || 0, parseFloat($('hotspotPitch').value) || 0);
        });
    });
    $('newHotspotBtn').addEventListener('click', function () { openForm(null); });
    $('hotspotCancel').addEventListener('click', closeForm);

    document.querySelectorAll('[data-edit-hotspot]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = +btn.dataset.editHotspot;
            openForm(hotspots.find(function (h) { return h.id === id; }));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    $('setInitialView').addEventListener('click', function () {
        var v = viewer.getView();
        var body = new FormData();
        body.append('csrf_token', csrf);
        body.append('id', sceneId);
        body.append('initial_yaw', v.yaw);
        body.append('initial_pitch', v.pitch);
        $('setViewMsg').textContent = 'Saving…';
        fetch(urls.setView, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) throw new Error(res.message);
                $('initialYaw').value = res.initial_yaw;
                $('initialPitch').value = res.initial_pitch;
                $('setViewMsg').textContent = 'Starting view saved ✓';
            })
            .catch(function (e) { $('setViewMsg').textContent = 'Could not save: ' + e.message; });
    });
})();
</script>
<?php
$scripts = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
