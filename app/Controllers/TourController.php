<?php
/**
 * Tour Controller
 *
 * Handles tour CRUD operations and the public viewer
 */

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
        $propertyId = (int) get_param('property_id') ?: null;
        $property = $propertyId ? $this->propertyModel->findForTenant($propertyId, $tenantId) : null;

        $tours = $this->tourModel->getByTenant($tenantId, $property ? $propertyId : null);

        view('tour.index', ['tours' => $tours, 'property' => $property]);
    }

    /**
     * Show create form. New tours start as drafts; publish once scenes exist.
     */
    public function create()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $properties = $this->propertyModel->where(['tenant_id' => $tenantId], 'title', 'ASC');

        if (!$this->subscriptionModel->getActiveSubscription($tenantId)) {
            flash('error', 'Your subscription is inactive. Please choose a plan to create tours.');
            redirect(base_url('?route=subscription'));
        }

        if (empty($properties)) {
            flash('info', 'Create a property first, then add a virtual tour to it.');
            redirect(base_url('?route=property/create'));
        }

        if (!is_post()) {
            view('tour.create', ['properties' => $properties, 'selectedPropertyId' => (int) get_param('property_id')]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour/create'));
        }

        $propertyId = (int) post('property_id');
        $title = sanitize(post('title'));

        $property = $this->propertyModel->findForTenant($propertyId, $tenantId);
        if (!$property || $title === '') {
            flash('error', !$property ? 'Invalid property selected' : 'Tour title is required');
            view('tour.create', ['properties' => $properties, 'selectedPropertyId' => $propertyId]);
            return;
        }

        $tourId = $this->tourModel->create([
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'title' => $title,
            'slug' => $this->tourModel->generateUniqueSlug($title),
            'description' => sanitize(post('description')),
            'status' => 'draft',
            'is_public' => post('is_public') ? 1 : 0,
            'auto_rotate' => post('auto_rotate') ? 1 : 0,
            'lead_form_enabled' => post('lead_form_enabled') ? 1 : 0,
            'created_by' => session_get('user_id')
        ]);

        flash('success', 'Tour created. Add your first 360° scene to get started.');
        redirect(base_url('?route=scene/create&tour_id=' . $tourId));
    }

    /**
     * View tour details with analytics
     */
    public function view()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tourId = (int) get_param('id');

        $tour = $this->tourModel->findForTenant($tourId, $tenantId);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $viewModel = new TourView();

        view('tour.view', [
            'tour' => $tour,
            'property' => $this->propertyModel->find($tour['property_id']),
            'scenes' => $this->sceneModel->getByTour($tourId, $tenantId),
            'daily' => $viewModel->dailyStats($tourId, 30),
            'totals' => $viewModel->totals($tourId),
            'referrers' => $viewModel->topReferrers($tourId),
            'leadsCount' => (new Lead())->count(['tour_id' => $tourId, 'tenant_id' => $tenantId]),
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

        $tour = $this->tourModel->findForTenant($tourId, $tenantId);

        if (!$tour) {
            flash('error', 'Tour not found');
            redirect(base_url('?route=tour'));
        }

        $properties = $this->propertyModel->where(['tenant_id' => $tenantId], 'title', 'ASC');

        if (!is_post()) {
            view('tour.edit', ['tour' => $tour, 'properties' => $properties]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour/edit&id=' . $tourId));
        }

        $propertyId = (int) post('property_id');
        $title = sanitize(post('title'));
        $status = enum_value(post('status'), Tour::STATUSES, 'draft');

        $errors = [];
        if (!$this->propertyModel->findForTenant($propertyId, $tenantId)) {
            $errors[] = 'Invalid property selected';
        }
        if ($title === '') {
            $errors[] = 'Tour title is required';
        }

        $isPublishing = $status === 'published' && $tour['status'] !== 'published';
        if ($isPublishing) {
            if ($this->sceneModel->count(['tour_id' => $tourId, 'tenant_id' => $tenantId]) === 0) {
                $errors[] = 'Add at least one scene before publishing';
            }
            $published = $this->tourModel->count(['tenant_id' => $tenantId, 'status' => 'published']);
            if (!$this->subscriptionModel->checkQuota($tenantId, 'active_tours', $published)) {
                $errors[] = 'Active (published) tour limit reached. Please upgrade your plan.';
            }
        }

        if ($errors) {
            flash('error', implode("\n", $errors));
            view('tour.edit', ['tour' => array_merge($tour, ['title' => $title, 'status' => $tour['status']]), 'properties' => $properties]);
            return;
        }

        $slug = $tour['slug'];
        if ($title !== $tour['title'] && post('regenerate_slug')) {
            $slug = $this->tourModel->generateUniqueSlug($title, null, $tourId);
        }

        $this->tourModel->update($tourId, [
            'property_id' => $propertyId,
            'title' => $title,
            'slug' => $slug,
            'description' => sanitize(post('description')),
            'status' => $status,
            'is_public' => post('is_public') ? 1 : 0,
            'is_featured' => post('is_featured') ? 1 : 0,
            'auto_rotate' => post('auto_rotate') ? 1 : 0,
            'lead_form_enabled' => post('lead_form_enabled') ? 1 : 0,
        ]);

        if ($isPublishing) {
            $tenant = (new Tenant())->find($tenantId);
            Webhook::dispatch($tenant, 'tour.published', [
                'tour_id' => $tourId,
                'title' => $title,
                'url' => tour_url($slug),
                'property_id' => $propertyId,
            ]);
        }

        flash('success', 'Tour updated successfully');
        redirect(base_url('?route=tour/view&id=' . $tourId));
    }

    /**
     * Delete tour (and its scene files)
     */
    public function delete()
    {
        TenantMiddleware::handle();

        $tenantId = current_tenant_id();
        $tourId = (int) post('id');

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=tour'));
        }

        $tour = $this->tourModel->findForTenant($tourId, $tenantId);

        if ($tour) {
            $scenes = $this->sceneModel->getByTour($tourId, $tenantId);
            $this->tourModel->delete($tourId);
            foreach ($scenes as $scene) {
                delete_upload($scene['image_path']);
                delete_upload($scene['thumbnail_path']);
            }
            flash('success', 'Tour deleted successfully');
        } else {
            flash('error', 'Tour not found');
        }

        redirect(base_url('?route=tour'));
    }

    /**
     * Public tour viewer: /tour/view/{slug}
     */
    public function viewer($slug = null)
    {
        $this->renderViewer($slug, false);
    }

    /**
     * Chrome-less viewer for iframes: /tour/embed/{slug}
     */
    public function embed($slug = null)
    {
        // Allow framing by any site for the embed route only
        header_remove('X-Frame-Options');
        header('Content-Security-Policy: frame-ancestors *');
        $this->renderViewer($slug, true);
    }

    private function renderViewer($slug, $isEmbed)
    {
        $slug = $slug ?: get_param('slug');
        $tour = $slug ? $this->tourModel->findBySlug($slug) : null;

        if (!$tour) {
            abort_404();
        }

        // Members of the owning tenant may preview drafts and private tours
        $isOwnerPreview = is_authenticated() && (int) current_tenant_id() === (int) $tour['tenant_id'];

        if (!$isOwnerPreview) {
            $isLive = $tour['status'] === 'published'
                && $tour['is_public']
                && $tour['tenant_status'] === 'active'
                && $this->subscriptionModel->getActiveSubscription($tour['tenant_id']);

            if (!$isLive) {
                abort_404();
            }
        }

        $scenes = $this->sceneModel->getByTour($tour['id'], $tour['tenant_id']);
        $hotspots = (new Hotspot())->getByTourGrouped($tour['id'], $tour['tenant_id']);

        $sceneData = [];
        foreach ($scenes as $scene) {
            $sceneData[] = [
                'id' => (int) $scene['id'],
                'name' => $scene['name'],
                'image' => asset($scene['image_path']),
                'thumb' => asset($scene['thumbnail_path'] ?: $scene['image_path']),
                'yaw' => (float) $scene['initial_yaw'],
                'pitch' => (float) $scene['initial_pitch'],
                'hotspots' => array_map(function ($h) {
                    return [
                        'id' => (int) $h['id'],
                        'type' => $h['type'],
                        'target' => $h['target_scene_id'] ? (int) $h['target_scene_id'] : null,
                        'yaw' => (float) $h['yaw'],
                        'pitch' => (float) $h['pitch'],
                        'label' => $h['label'],
                        'description' => $h['description'],
                        'url' => is_safe_url($h['external_url']) ? $h['external_url'] : null,
                    ];
                }, $hotspots[$scene['id']] ?? []),
            ];
        }

        if (!$isOwnerPreview && $sceneData) {
            $this->tourModel->incrementViews($tour['id']);
            (new TourView())->record($tour, $isEmbed);
        }

        view('tour.viewer', [
            'tour' => $tour,
            'scenes' => $sceneData,
            'isEmbed' => $isEmbed,
            'isPreview' => $isOwnerPreview,
            'brandColor' => is_hex_color($tour['brand_color']) ? $tour['brand_color'] : '#3498db',
        ]);
    }
}
