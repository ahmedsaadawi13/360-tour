<?php
/**
 * Scene Model
 */

require_once __DIR__ . '/BaseModel.php';

class Scene extends BaseModel
{
    protected $table = 'scenes';
    protected $fillable = [
        'tenant_id', 'tour_id', 'name', 'image_path', 'thumbnail_path',
        'initial_yaw', 'initial_pitch', 'order_index'
    ];

    /**
     * Get scenes by tour
     */
    public function getByTour($tourId, $tenantId)
    {
        return $this->where([
            'tour_id' => $tourId,
            'tenant_id' => $tenantId
        ], 'order_index', 'ASC');
    }

    /**
     * Get scene with hotspots
     */
    public function getWithHotspots($sceneId, $tenantId)
    {
        $scene = $this->first(['id' => $sceneId, 'tenant_id' => $tenantId]);

        if ($scene) {
            $sql = "SELECT * FROM hotspots WHERE scene_id = ? AND tenant_id = ? ORDER BY created_at";
            $stmt = $this->query($sql, [$sceneId, $tenantId]);
            $scene['hotspots'] = $stmt->fetchAll();
        }

        return $scene;
    }

    /**
     * Get next order index for tour
     */
    public function getNextOrderIndex($tourId)
    {
        $sql = "SELECT MAX(order_index) as max_order FROM {$this->table} WHERE tour_id = ?";
        $stmt = $this->query($sql, [$tourId]);
        $result = $stmt->fetch();

        return ($result['max_order'] ?? 0) + 1;
    }
}
