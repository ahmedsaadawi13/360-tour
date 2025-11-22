<?php
/**
 * Tour Controller
 *
 * Handles tour CRUD operations
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/Tour.php';
require_once __DIR__ . '/../Models/Property.php';
require_once __DIR__ . '/../Models/Scene.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';

class TourController
{
    private $tourModel;
    private $propertyModel;
    private $sceneModel;
    private $subscriptionModel;

    public function __construct()
    {
        $this->tourModel = new Tour();
        $this->propertyModel = new Property();
        $this->sceneModel = new Scene();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * List all tours
     */
    public function index()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tours = $this->tourModel->getByTenant($tenantId);

        view('tour.index', ['tours' => $tours]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $propertyId = (int) get_param('property_id');

        // Check quota
        $currentCount = $this->tourModel->count(['tenant_id' => $tenantId, 'status' => 'published']);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'active_tours', $currentCount)) {
            flash('error', 'Active tour limit reached. Please upgrade your plan.');
            redirect(base_url('?route=tour'));
        }

        $properties = $this->propertyModel->where(['tenant_id' => $tenantId]);

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=tour/create'));
            }

            $propertyId = (int) post('property_id');
            $title = sanitize(post('title'));

            // Verify property belongs to tenant
            $property = $this->propertyModel->first(['id' => $propertyId, 'tenant_id' => $tenantId]);
            if (!$property) {
                flash('error', 'Invalid property selected');
                view('tour.create', ['properties' => $properties, 'selectedPropertyId' => $propertyId]);
                return;
            }

            $slug = $this->tourModel->generateUniqueSlug($title, $tenantId);

            $data = [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'title' => $title,
                'slug' => $slug,
                'description' => sanitize(post('description')),
                'status' => sanitize(post('status')),
                'is_public' => post('is_public') ? 1 : 0,
                'created_by' => session_get('user_id')
            ];

            $tourId = $this->tourModel->create($data);

            flash('success', 'Tour created successfully');
            redirect(base_url('?route=tour/view&id=' . $tourId));
        } else {
            view('tour.create', ['properties' => $properties, 'selectedPropertyId' => $propertyId]);
        }
    }

    /**
     * View tour details
     */
    public function view()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tourId = (int) get_param('id');

        $tour = $this->tourModel->first(['id' => $tourId, 'tenant_id' => $tenantId]);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $property = $this->propertyModel->find($tour['property_id']);
        $scenes = $this->sceneModel->getByTour($tourId, $tenantId);

        view('tour.view', [
            'tour' => $tour,
            'property' => $property,
            'scenes' => $scenes
        ]);
    }

    /**
     * Edit tour
     */
    public function edit()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tourId = (int) get_param('id');

        $tour = $this->tourModel->first(['id' => $tourId, 'tenant_id' => $tenantId]);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $properties = $this->propertyModel->where(['tenant_id' => $tenantId]);

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=tour/edit&id=' . $tourId));
            }

            $propertyId = (int) post('property_id');
            $title = sanitize(post('title'));

            // Verify property belongs to tenant
            $property = $this->propertyModel->first(['id' => $propertyId, 'tenant_id' => $tenantId]);
            if (!$property) {
                flash('error', 'Invalid property selected');
                view('tour.edit', ['tour' => $tour, 'properties' => $properties]);
                return;
            }

            // Regenerate slug if title changed
            $slug = $tour['slug'];
            if ($title !== $tour['title']) {
                $slug = $this->tourModel->generateUniqueSlug($title, $tenantId, $tourId);
            }

            $data = [
                'property_id' => $propertyId,
                'title' => $title,
                'slug' => $slug,
                'description' => sanitize(post('description')),
                'status' => sanitize(post('status')),
                'is_public' => post('is_public') ? 1 : 0
            ];

            $this->tourModel->update($tourId, $data);

            flash('success', 'Tour updated successfully');
            redirect(base_url('?route=tour/view&id=' . $tourId));
        } else {
            view('tour.edit', ['tour' => $tour, 'properties' => $properties]);
        }
    }

    /**
     * Delete tour
     */
    public function delete()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tourId = (int) get_param('id');

        if (!csrf_verify(get_param('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $tour = $this->tourModel->first(['id' => $tourId, 'tenant_id' => $tenantId]);

        if ($tour) {
            $this->tourModel->delete($tourId);
            flash('success', 'Tour deleted successfully');
        } else {
            flash('error', 'Tour not found');
        }

        redirect(base_url('?route=tour'));
    }

    /**
     * Public tour viewer
     */
    public function viewer()
    {
        $slug = get_param('slug');

        if (!$slug) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $tour = $this->tourModel->findBySlug($slug);

        if (!$tour || $tour['status'] !== 'published' || !$tour['is_public']) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        // Increment view count
        $this->tourModel->incrementViews($tour['id']);

        // Get scenes with hotspots
        $scenes = $this->sceneModel->getByTour($tour['id'], $tour['tenant_id']);

        // Load hotspots for each scene
        require_once __DIR__ . '/../Models/Hotspot.php';
        $hotspotModel = new Hotspot();

        foreach ($scenes as &$scene) {
            $scene['hotspots'] = $hotspotModel->getByScene($scene['id'], $tour['tenant_id']);
        }

        view('tour.viewer', [
            'tour' => $tour,
            'scenes' => $scenes
        ]);
    }
}
