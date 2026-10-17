<?php
/**
 * Hotspot Model
 */

require_once __DIR__ . '/BaseModel.php';

class Hotspot extends BaseModel
{
    const TYPES = ['navigation', 'info', 'link'];

    protected $table = 'hotspots';
    protected $fillable = [
        'tenant_id', 'scene_id', 'type', 'target_scene_id', 'yaw', 'pitch',
        'label', 'description', 'external_url', 'icon_type'
    ];

    /**
     * Get hotspots by scene
     */
    public function getByScene($sceneId, $tenantId)
    {
        return $this->where([
            'scene_id' => $sceneId,
            'tenant_id' => $tenantId
        ], 'id', 'ASC');
    }

    /**
     * All hotspots of a tour grouped by scene id (avoids one query per scene)
     */
    public function getByTourGrouped($tourId, $tenantId)
    {
        $sql = "SELECT h.id, h.scene_id, h.type, h.target_scene_id, h.yaw, h.pitch,
                       h.label, h.description, h.external_url, h.icon_type
                FROM {$this->table} h
                JOIN scenes s ON h.scene_id = s.id
                WHERE s.tour_id = ? AND h.tenant_id = ?
                ORDER BY h.id";

        $grouped = [];
        foreach ($this->query($sql, [$tourId, $tenantId])->fetchAll() as $row) {
            $grouped[$row['scene_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Get hotspots with target scene names
     */
    public function getBySceneWithTargets($sceneId, $tenantId)
    {
        $sql = "SELECT h.*, s.name as target_scene_name
                FROM {$this->table} h
                LEFT JOIN scenes s ON h.target_scene_id = s.id
                WHERE h.scene_id = ? AND h.tenant_id = ?
                ORDER BY h.id";

        $stmt = $this->query($sql, [$sceneId, $tenantId]);
        return $stmt->fetchAll();
    }
}
