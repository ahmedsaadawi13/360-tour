<?php
/**
 * Hotspot Controller
 *
 * Handles hotspot CRUD operations
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/Hotspot.php';
require_once __DIR__ . '/../Models/Scene.php';

class HotspotController
{
    private $hotspotModel;
    private $sceneModel;

    public function __construct()
    {
        TenantMiddleware::handle();

        $this->hotspotModel = new Hotspot();
        $this->sceneModel = new Scene();
    }

    /**
     * Create hotspot
     */
    public function create()
    {
        $tenantId = current_tenant_id();
        $sceneId = (int) post('scene_id');

        if (!csrf_verify(post('csrf_token'))) {
            json_response(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $scene = $this->sceneModel->first(['id' => $sceneId, 'tenant_id' => $tenantId]);

        if (!$scene) {
            json_response(['success' => false, 'message' => 'Scene not found'], 404);
        }

        $data = [
            'tenant_id' => $tenantId,
            'scene_id' => $sceneId,
            'type' => sanitize(post('type')),
            'target_scene_id' => post('target_scene_id') ? (int) post('target_scene_id') : null,
            'yaw' => (float) post('yaw'),
            'pitch' => (float) post('pitch'),
            'label' => sanitize(post('label')),
            'description' => sanitize(post('description')),
            'external_url' => sanitize(post('external_url')),
            'icon_type' => sanitize(post('icon_type'))
        ];

        $hotspotId = $this->hotspotModel->create($data);

        flash('success', 'Hotspot created successfully');
        redirect(base_url('?route=scene/edit&id=' . $sceneId));
    }

    /**
     * Update hotspot
     */
    public function update()
    {
        $tenantId = current_tenant_id();
        $hotspotId = (int) post('id');

        if (!csrf_verify(post('csrf_token'))) {
            json_response(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $hotspot = $this->hotspotModel->first(['id' => $hotspotId, 'tenant_id' => $tenantId]);

        if (!$hotspot) {
            json_response(['success' => false, 'message' => 'Hotspot not found'], 404);
        }

        $data = [
            'type' => sanitize(post('type')),
            'target_scene_id' => post('target_scene_id') ? (int) post('target_scene_id') : null,
            'yaw' => (float) post('yaw'),
            'pitch' => (float) post('pitch'),
            'label' => sanitize(post('label')),
            'description' => sanitize(post('description')),
            'external_url' => sanitize(post('external_url')),
            'icon_type' => sanitize(post('icon_type'))
        ];

        $this->hotspotModel->update($hotspotId, $data);

        flash('success', 'Hotspot updated successfully');
        redirect(base_url('?route=scene/edit&id=' . $hotspot['scene_id']));
    }

    /**
     * Delete hotspot
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $hotspotId = (int) get_param('id');

        if (!csrf_verify(get_param('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $hotspot = $this->hotspotModel->first(['id' => $hotspotId, 'tenant_id' => $tenantId]);

        if ($hotspot) {
            $sceneId = $hotspot['scene_id'];
            $this->hotspotModel->delete($hotspotId);
            flash('success', 'Hotspot deleted successfully');
            redirect(base_url('?route=scene/edit&id=' . $sceneId));
        } else {
            flash('error', 'Hotspot not found');
            redirect(base_url('?route=tour'));
        }
    }
}
