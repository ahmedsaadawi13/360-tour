<?php
/**
 * Plan Model
 */

require_once __DIR__ . '/BaseModel.php';

class Plan extends BaseModel
{
    protected $table = 'plans';
    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'max_properties', 'max_active_tours', 'max_scenes', 'max_users',
        'features', 'is_active', 'sort_order'
    ];

    /**
     * Get all active plans
     */
    public function getActivePlans()
    {
        return $this->where(['is_active' => 1], 'sort_order', 'ASC');
    }

    /**
     * Get plan by slug
     */
    public function findBySlug($slug)
    {
        return $this->first(['slug' => $slug]);
    }

    /**
     * Get plan features as array
     */
    public function getFeaturesArray($plan)
    {
        if (empty($plan['features'])) {
            return [];
        }
        return json_decode($plan['features'], true) ?? [];
    }
}
