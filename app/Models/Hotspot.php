<?php
/**
 * Hotspot Model
 */

require_once __DIR__ . '/BaseModel.php';

class Hotspot extends BaseModel
{
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
        ], 'created_at', 'ASC');
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
                ORDER BY h.created_at";

        $stmt = $this->query($sql, [$sceneId, $tenantId]);
        return $stmt->fetchAll();
    }
}
