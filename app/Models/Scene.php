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
        $sql = "SELECT * FROM {$this->table} WHERE tour_id = ? AND tenant_id = ? ORDER BY order_index ASC, id ASC";
        return $this->query($sql, [$tourId, $tenantId])->fetchAll();
    }

    /**
     * Move a scene one position up or down within its tour
     */
    public function move($scene, $direction)
    {
        $scenes = $this->getByTour($scene['tour_id'], $scene['tenant_id']);
        $ids = array_column($scenes, 'id');
        $pos = array_search($scene['id'], $ids);
        $swap = $direction === 'up' ? $pos - 1 : $pos + 1;

        if ($pos === false || $swap < 0 || $swap >= count($ids)) {
            return false;
        }

        list($ids[$pos], $ids[$swap]) = [$ids[$swap], $ids[$pos]];

        // Renumber the whole tour so duplicate/legacy indexes are normalized
        $this->beginTransaction();
        $stmt = $this->db->prepare("UPDATE {$this->table} SET order_index = ? WHERE id = ?");
        foreach ($ids as $index => $id) {
            $stmt->execute([$index + 1, $id]);
        }
        $this->commit();

        return true;
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
