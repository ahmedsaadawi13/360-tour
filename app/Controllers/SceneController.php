<?php
/**
 * Scene Controller
 *
 * Handles scene CRUD operations
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/Scene.php';
require_once __DIR__ . '/../Models/Tour.php';
require_once __DIR__ . '/../Models/Hotspot.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';

class SceneController
{
    private $sceneModel;
    private $tourModel;
    private $hotspotModel;
    private $subscriptionModel;

    public function __construct()
    {
        TenantMiddleware::handle();

        $this->sceneModel = new Scene();
        $this->tourModel = new Tour();
        $this->hotspotModel = new Hotspot();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * List scenes for a tour
     */
    public function index()
    {
        $tenantId = current_tenant_id();
        $tourId = (int) get_param('tour_id');

        $tour = $this->tourModel->first(['id' => $tourId, 'tenant_id' => $tenantId]);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $scenes = $this->sceneModel->getByTour($tourId, $tenantId);

        view('scene.index', ['tour' => $tour, 'scenes' => $scenes]);
    }

    /**
     * Create scene
     */
    public function create()
    {
        $tenantId = current_tenant_id();
        $tourId = (int) get_param('tour_id');

        // Check quota
        $currentCount = $this->sceneModel->count(['tenant_id' => $tenantId]);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'scenes', $currentCount)) {
            flash('error', 'Scene limit reached. Please upgrade your plan.');
            redirect(base_url('?route=scene?tour_id=' . $tourId));
        }

        $tour = $this->tourModel->first(['id' => $tourId, 'tenant_id' => $tenantId]);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=scene/create&tour_id=' . $tourId));
            }

            // Handle image upload
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                flash('error', '360° image is required');
                view('scene.create', ['tour' => $tour]);
                return;
            }

            $uploadResult = upload_file(
                $_FILES['image'],
                __DIR__ . '/../../public/uploads/scenes',
                ['image/jpeg', 'image/jpg', 'image/png']
            );

            if (!$uploadResult['success']) {
                flash('error', $uploadResult['message']);
                view('scene.create', ['tour' => $tour]);
                return;
            }

            $data = [
                'tenant_id' => $tenantId,
                'tour_id' => $tourId,
                'name' => sanitize(post('name')),
                'image_path' => '/uploads/scenes/' . $uploadResult['filename'],
                'initial_yaw' => post('initial_yaw') ? (float) post('initial_yaw') : 0,
                'initial_pitch' => post('initial_pitch') ? (float) post('initial_pitch') : 0,
                'order_index' => $this->sceneModel->getNextOrderIndex($tourId)
            ];

            $sceneId = $this->sceneModel->create($data);

            flash('success', 'Scene created successfully');
            redirect(base_url('?route=scene/edit&id=' . $sceneId));
        } else {
            view('scene.create', ['tour' => $tour]);
        }
    }

    /**
     * Edit scene and manage hotspots
     */
    public function edit()
    {
        $tenantId = current_tenant_id();
        $sceneId = (int) get_param('id');

        $scene = $this->sceneModel->first(['id' => $sceneId, 'tenant_id' => $tenantId]);

        if (!$scene) {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }

        $tour = $this->tourModel->find($scene['tour_id']);
        $hotspots = $this->hotspotModel->getBySceneWithTargets($sceneId, $tenantId);
        $availableScenes = $this->sceneModel->getByTour($tour['id'], $tenantId);

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=scene/edit&id=' . $sceneId));
            }

            $data = [
                'name' => sanitize(post('name')),
                'initial_yaw' => post('initial_yaw') ? (float) post('initial_yaw') : 0,
                'initial_pitch' => post('initial_pitch') ? (float) post('initial_pitch') : 0,
                'order_index' => post('order_index') ? (int) post('order_index') : $scene['order_index']
            ];

            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = upload_file(
                    $_FILES['image'],
                    __DIR__ . '/../../public/uploads/scenes',
                    ['image/jpeg', 'image/jpg', 'image/png']
                );

                if ($uploadResult['success']) {
                    $data['image_path'] = '/uploads/scenes/' . $uploadResult['filename'];
                }
            }

            $this->sceneModel->update($sceneId, $data);

            flash('success', 'Scene updated successfully');
            redirect(base_url('?route=scene/edit&id=' . $sceneId));
        } else {
            view('scene.edit', [
                'scene' => $scene,
                'tour' => $tour,
                'hotspots' => $hotspots,
                'availableScenes' => $availableScenes
            ]);
        }
    }

    /**
     * Delete scene
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $sceneId = (int) get_param('id');

        if (!csrf_verify(get_param('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $scene = $this->sceneModel->first(['id' => $sceneId, 'tenant_id' => $tenantId]);

        if ($scene) {
            $tourId = $scene['tour_id'];
            $this->sceneModel->delete($sceneId);
            flash('success', 'Scene deleted successfully');
            redirect(base_url('?route=scene?tour_id=' . $tourId));
        } else {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }
    }
}
