<?php
/**
 * Hotspot Controller
 *
 * Handles hotspot CRUD operations
 */

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
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $scene = $this->sceneModel->findForTenant($sceneId, $tenantId);

        if (!$scene) {
            flash('error', 'Scene not found');
            redirect(base_url('?route=tour'));
        }

        list($data, $errors) = $this->collectInput($scene);

        if ($errors) {
            flash('error', implode("\n", $errors));
        } else {
            $this->hotspotModel->create($data + ['tenant_id' => $tenantId, 'scene_id' => $sceneId]);
            flash('success', 'Hotspot created successfully');
        }

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
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $hotspot = $this->hotspotModel->findForTenant($hotspotId, $tenantId);

        if (!$hotspot) {
            flash('error', 'Hotspot not found');
            redirect(base_url('?route=tour'));
        }

        $scene = $this->sceneModel->find($hotspot['scene_id']);
        list($data, $errors) = $this->collectInput($scene);

        if ($errors) {
            flash('error', implode("\n", $errors));
        } else {
            $this->hotspotModel->update($hotspotId, $data);
            flash('success', 'Hotspot updated successfully');
        }

        redirect(base_url('?route=scene/edit&id=' . $hotspot['scene_id']));
    }

    /**
     * Delete hotspot
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $hotspotId = (int) post('id');

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $hotspot = $this->hotspotModel->findForTenant($hotspotId, $tenantId);

        if (!$hotspot) {
            flash('error', 'Hotspot not found');
            redirect(base_url('?route=tour'));
        }

        $this->hotspotModel->delete($hotspotId);
        flash('success', 'Hotspot deleted successfully');
        redirect(base_url('?route=scene/edit&id=' . $hotspot['scene_id']));
    }

    /**
     * Validate hotspot fields. Navigation targets must be another scene of
     * the same tour (previously any scene id, even another tenant's, was accepted).
     */
    private function collectInput(array $scene)
    {
        $errors = [];
        $type = enum_value(post('type'), Hotspot::TYPES);
        if (!$type) {
            $errors[] = 'Invalid hotspot type';
        }

        $targetSceneId = null;
        $externalUrl = null;

        if ($type === 'navigation') {
            $target = $this->sceneModel->findForTenant((int) post('target_scene_id'), $scene['tenant_id']);
            if (!$target || $target['tour_id'] != $scene['tour_id'] || $target['id'] == $scene['id']) {
                $errors[] = 'Choose another scene of this tour as the navigation target';
            } else {
                $targetSceneId = (int) $target['id'];
            }
        }

        if ($type === 'link') {
            $externalUrl = trim((string) post('external_url'));
            if (!is_safe_url($externalUrl)) {
                $errors[] = 'External URL must start with http:// or https://';
            }
        }

        $label = sanitize(post('label'));
        if ($label === '' && $type !== 'navigation') {
            $errors[] = 'Label is required';
        }

        $yaw = fmod((float) post('yaw'), 360);
        if ($yaw > 180) $yaw -= 360;
        if ($yaw < -180) $yaw += 360;

        $data = [
            'type' => $type,
            'target_scene_id' => $targetSceneId,
            'yaw' => round($yaw, 2),
            'pitch' => round(max(-90, min(90, (float) post('pitch'))), 2),
            'label' => mb_substr($label, 0, 255) ?: null,
            'description' => sanitize(post('description')) ?: null,
            'external_url' => $externalUrl,
            'icon_type' => ['navigation' => 'arrow', 'info' => 'info', 'link' => 'link'][$type] ?? 'arrow',
        ];

        return [$data, $errors];
    }
}
