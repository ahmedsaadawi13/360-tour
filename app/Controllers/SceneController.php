<?php
/**
 * Scene Controller
 *
 * Handles scene CRUD operations
 */

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

        $tour = $this->tourModel->findForTenant($tourId, $tenantId);

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

        $tour = $this->tourModel->findForTenant($tourId, $tenantId);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $currentCount = $this->sceneModel->count(['tenant_id' => $tenantId]);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'scenes', $currentCount)) {
            flash('error', 'Scene limit reached or subscription inactive. Please upgrade your plan.');
            redirect(base_url('?route=scene&tour_id=' . $tourId));
        }

        if (!is_post()) {
            view('scene.create', ['tour' => $tour]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=scene/create&tour_id=' . $tourId));
        }

        $name = sanitize(post('name'));
        if ($name === '') {
            flash('error', 'Scene name is required');
            view('scene.create', ['tour' => $tour]);
            return;
        }

        if (!has_upload('image')) {
            flash('error', '360° image is required');
            view('scene.create', ['tour' => $tour]);
            return;
        }

        $upload = $this->uploadPanorama();
        if (!$upload['success']) {
            flash('error', $upload['message']);
            view('scene.create', ['tour' => $tour]);
            return;
        }

        $sceneId = $this->sceneModel->create([
            'tenant_id' => $tenantId,
            'tour_id' => $tourId,
            'name' => $name,
            'image_path' => '/uploads/scenes/' . $upload['filename'],
            'thumbnail_path' => $upload['thumbnail'],
            'initial_yaw' => $this->normalizeYaw(post('initial_yaw')),
            'initial_pitch' => $this->clampPitch(post('initial_pitch')),
            'order_index' => $this->sceneModel->getNextOrderIndex($tourId)
        ]);

        $message = 'Scene created. Click on the panorama to place hotspots.';
        if (abs($upload['width'] / $upload['height'] - 2) > 0.1) {
            flash('warning', 'This image is not 2:1. Equirectangular 360° panoramas (e.g. 6000x3000) display best.');
        }

        flash('success', $message);
        redirect(base_url('?route=scene/edit&id=' . $sceneId));
    }

    /**
     * Edit scene and manage hotspots (visual editor)
     */
    public function edit()
    {
        $tenantId = current_tenant_id();
        $sceneId = (int) get_param('id');

        $scene = $this->sceneModel->findForTenant($sceneId, $tenantId);

        if (!$scene) {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }

        $tour = $this->tourModel->find($scene['tour_id']);

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=scene/edit&id=' . $sceneId));
            }

            $data = [
                'name' => sanitize(post('name')) ?: $scene['name'],
                'initial_yaw' => $this->normalizeYaw(post('initial_yaw')),
                'initial_pitch' => $this->clampPitch(post('initial_pitch')),
            ];

            if (has_upload('image')) {
                $upload = $this->uploadPanorama();
                if (!$upload['success']) {
                    flash('error', $upload['message']);
                    redirect(base_url('?route=scene/edit&id=' . $sceneId));
                }
                $data['image_path'] = '/uploads/scenes/' . $upload['filename'];
                $data['thumbnail_path'] = $upload['thumbnail'];
            }

            $this->sceneModel->update($sceneId, $data);

            if (isset($data['image_path'])) {
                delete_upload($scene['image_path']);
                delete_upload($scene['thumbnail_path']);
            }

            flash('success', 'Scene updated successfully');
            redirect(base_url('?route=scene/edit&id=' . $sceneId));
        }

        view('scene.edit', [
            'scene' => $scene,
            'tour' => $tour,
            'hotspots' => $this->hotspotModel->getBySceneWithTargets($sceneId, $tenantId),
            'availableScenes' => $this->sceneModel->getByTour($tour['id'], $tenantId)
        ]);
    }

    /**
     * Save the initial view from the visual editor (AJAX)
     */
    public function setView()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            json_response(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $scene = $this->sceneModel->findForTenant((int) post('id'), $tenantId);
        if (!$scene) {
            json_response(['success' => false, 'message' => 'Scene not found'], 404);
        }

        $yaw = $this->normalizeYaw(post('initial_yaw'));
        $pitch = $this->clampPitch(post('initial_pitch'));
        $this->sceneModel->update($scene['id'], ['initial_yaw' => $yaw, 'initial_pitch' => $pitch]);

        json_response(['success' => true, 'initial_yaw' => $yaw, 'initial_pitch' => $pitch]);
    }

    /**
     * Move scene up/down in the tour order
     */
    public function move()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $scene = $this->sceneModel->findForTenant((int) post('id'), $tenantId);
        if (!$scene) {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }

        $this->sceneModel->move($scene, post('direction') === 'up' ? 'up' : 'down');
        redirect(base_url('?route=scene&tour_id=' . $scene['tour_id']));
    }

    /**
     * Delete scene
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $sceneId = (int) post('id');

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $scene = $this->sceneModel->findForTenant($sceneId, $tenantId);

        if (!$scene) {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }

        $this->sceneModel->delete($sceneId);
        delete_upload($scene['image_path']);
        delete_upload($scene['thumbnail_path']);

        // A published tour must keep at least one scene
        if ($this->sceneModel->count(['tour_id' => $scene['tour_id']]) === 0) {
            $this->tourModel->update($scene['tour_id'], ['status' => 'draft']);
            flash('warning', 'The tour has no scenes left and was moved back to draft.');
        }

        flash('success', 'Scene deleted successfully');
        redirect(base_url('?route=scene&tour_id=' . $scene['tour_id']));
    }

    /**
     * Upload a panorama and generate its thumbnail
     */
    private function uploadPanorama()
    {
        $upload = upload_file($_FILES['image'], config('upload_path') . '/scenes');

        if ($upload['success']) {
            $thumb = ImageService::makeThumbnail($upload['path']);
            $upload['thumbnail'] = $thumb ? '/uploads/scenes/' . $thumb : null;
        }

        return $upload;
    }

    private function normalizeYaw($value)
    {
        $yaw = fmod((float) $value, 360);
        if ($yaw > 180) $yaw -= 360;
        if ($yaw < -180) $yaw += 360;
        return round($yaw, 2);
    }

    private function clampPitch($value)
    {
        return round(max(-90, min(90, (float) $value)), 2);
    }
}
