<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tour['title']); ?> - Virtual Tour</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .tour-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 30px;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tour-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .tour-info {
            font-size: 0.9rem;
        }
        .viewer-wrapper {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 100px;
        }
        .viewer-container {
            width: 100%;
            height: 100%;
            background: #000;
        }
        .scene-list {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            padding: 15px 30px;
            display: flex;
            gap: 15px;
            overflow-x: auto;
            z-index: 100;
        }
        .scene-item {
            min-width: 120px;
            text-align: center;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 10px;
            transition: all 0.3s;
            color: white;
        }
        .scene-item:hover,
        .scene-item.active {
            border-color: var(--primary-color);
            background: rgba(52, 152, 219, 0.2);
        }
        .scene-thumb {
            width: 100px;
            height: 70px;
            background-size: cover;
            background-position: center;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .info-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            display: none;
            z-index: 200;
        }
        .info-popup.active {
            display: block;
        }
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            z-index: 199;
        }
        .popup-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="tour-header">
        <div>
            <h1><?php echo e($tour['title']); ?></h1>
            <div class="tour-info">
                <?php echo e($tour['property_title']); ?>
                <?php if ($tour['city']): ?>
                    • <?php echo e($tour['city']); ?>
                <?php endif; ?>
                <?php if ($tour['price']): ?>
                    • <?php echo format_currency($tour['price'], $tour['currency']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="viewer-wrapper">
        <div id="viewer360" class="viewer-container"></div>
    </div>

    <div class="scene-list">
        <?php foreach ($scenes as $index => $scene): ?>
            <div class="scene-item <?php echo $index === 0 ? 'active' : ''; ?>"
                 onclick="switchScene(<?php echo $index; ?>)"
                 data-scene-index="<?php echo $index; ?>">
                <div class="scene-thumb" style="background-image: url('<?php echo asset($scene['image_path']); ?>');"></div>
                <div><?php echo e($scene['name']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="popup-overlay" id="popupOverlay" onclick="closePopup()"></div>
    <div class="info-popup" id="infoPopup">
        <h2 id="popupTitle"></h2>
        <p id="popupDescription"></p>
        <button onclick="closePopup()" class="btn btn-primary">Close</button>
    </div>

    <script src="<?php echo asset('js/viewer360.js'); ?>"></script>
    <script>
        // Scene data
        const scenes = <?php echo json_encode($scenes); ?>;
        let currentSceneIndex = 0;
        let viewer = null;

        // Initialize viewer
        window.addEventListener('load', function() {
            viewer = new Viewer360('viewer360', {
                initialYaw: parseFloat(scenes[0].initial_yaw),
                initialPitch: parseFloat(scenes[0].initial_pitch),
                hotspots: scenes[0].hotspots,
                onHotspotClick: handleHotspotClick
            });

            viewer.loadPanorama(
                '<?php echo asset($scenes[0]['image_path']); ?>',
                parseFloat(scenes[0].initial_yaw),
                parseFloat(scenes[0].initial_pitch),
                scenes[0].hotspots
            );
        });

        // Switch to different scene
        function switchScene(index) {
            currentSceneIndex = index;
            const scene = scenes[index];

            viewer.loadPanorama(
                '<?php echo asset(''); ?>' + scene.image_path,
                parseFloat(scene.initial_yaw),
                parseFloat(scene.initial_pitch),
                scene.hotspots
            );

            // Update active state
            document.querySelectorAll('.scene-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-scene-index="${index}"]`).classList.add('active');
        }

        // Handle hotspot clicks
        function handleHotspotClick(hotspot) {
            if (hotspot.type === 'navigation') {
                // Find target scene index
                const targetIndex = scenes.findIndex(s => s.id == hotspot.target_scene_id);
                if (targetIndex !== -1) {
                    switchScene(targetIndex);
                }
            } else if (hotspot.type === 'info') {
                // Show info popup
                document.getElementById('popupTitle').textContent = hotspot.label;
                document.getElementById('popupDescription').textContent = hotspot.description || 'No description available';
                document.getElementById('infoPopup').classList.add('active');
                document.getElementById('popupOverlay').classList.add('active');
            } else if (hotspot.type === 'link') {
                // Open external link
                if (hotspot.external_url) {
                    window.open(hotspot.external_url, '_blank');
                }
            }
        }

        // Close info popup
        function closePopup() {
            document.getElementById('infoPopup').classList.remove('active');
            document.getElementById('popupOverlay').classList.remove('active');
        }
    </script>
</body>
</html>
