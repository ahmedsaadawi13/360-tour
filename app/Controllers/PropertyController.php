<?php
/**
 * Property Controller
 *
 * Handles property CRUD operations
 */

class PropertyController
{
    private $propertyModel;
    private $subscriptionModel;

    public function __construct()
    {
        TenantMiddleware::handle();
        $this->propertyModel = new Property();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * List all properties
     */
    public function index()
    {
        $tenantId = current_tenant_id();

        $filters = [
            'type' => enum_value(get_param('type'), Property::TYPES),
            'status' => enum_value(get_param('status'), Property::STATUSES),
            'city' => sanitize(get_param('city')),
            'search' => sanitize(get_param('search'))
        ];

        $properties = $this->propertyModel->getByTenantFiltered($tenantId, $filters);

        view('property.index', [
            'properties' => $properties,
            'filters' => $filters,
            'cities' => $this->propertyModel->getCities($tenantId)
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $tenantId = current_tenant_id();

        $currentCount = $this->propertyModel->count(['tenant_id' => $tenantId]);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'properties', $currentCount)) {
            flash('error', 'Property limit reached or subscription inactive. Please upgrade your plan.');
            redirect(base_url('?route=property'));
        }

        if (!is_post()) {
            view('property.create');
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=property/create'));
        }

        $data = $this->collectInput();
        $errors = $this->validate($data);

        if (!$errors && has_upload('main_image')) {
            $upload = $this->uploadImage();
            if ($upload['success']) {
                $data['main_image'] = '/uploads/properties/' . $upload['filename'];
            } else {
                $errors[] = 'Image: ' . $upload['message'];
            }
        }

        if ($errors) {
            flash('error', implode("\n", $errors));
            set_old_input($_POST);
            view('property.create');
            return;
        }

        clear_old_input();
        $data['tenant_id'] = $tenantId;
        $data['created_by'] = session_get('user_id');
        $propertyId = $this->propertyModel->create($data);

        flash('success', 'Property created successfully');
        redirect(base_url('?route=property/view&id=' . $propertyId));
    }

    /**
     * Show property details
     */
    public function view()
    {
        $tenantId = current_tenant_id();
        $propertyId = (int) get_param('id');

        $property = $this->propertyModel->getWithToursCount($propertyId, $tenantId);

        if (!$property) {
            flash('error', 'Property not found');
            redirect(base_url('?route=property'));
        }

        $tours = (new Tour())->getByTenant($tenantId, $propertyId);

        view('property.view', ['property' => $property, 'tours' => $tours]);
    }

    /**
     * Show edit form
     */
    public function edit()
    {
        $tenantId = current_tenant_id();
        $propertyId = (int) get_param('id');

        $property = $this->propertyModel->findForTenant($propertyId, $tenantId);

        if (!$property) {
            flash('error', 'Property not found');
            redirect(base_url('?route=property'));
        }

        if (!is_post()) {
            view('property.edit', ['property' => $property]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=property/edit&id=' . $propertyId));
        }

        $data = $this->collectInput();
        $errors = $this->validate($data);

        if (!$errors && has_upload('main_image')) {
            $upload = $this->uploadImage();
            if ($upload['success']) {
                $data['main_image'] = '/uploads/properties/' . $upload['filename'];
            } else {
                $errors[] = 'Image: ' . $upload['message'];
            }
        }

        if ($errors) {
            flash('error', implode("\n", $errors));
            view('property.edit', ['property' => array_merge($property, $data)]);
            return;
        }

        $this->propertyModel->update($propertyId, $data);

        if (isset($data['main_image']) && $property['main_image']) {
            delete_upload($property['main_image']);
        }

        flash('success', 'Property updated successfully');
        redirect(base_url('?route=property/view&id=' . $propertyId));
    }

    /**
     * Delete property (and its tours, scenes and files)
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $propertyId = (int) post('id');

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=property'));
        }

        $property = $this->propertyModel->findForTenant($propertyId, $tenantId);

        if (!$property) {
            flash('error', 'Property not found');
            redirect(base_url('?route=property'));
        }

        // Collect scene files before the cascade removes their rows
        $sceneModel = new Scene();
        $files = [$property['main_image']];
        foreach ((new Tour())->getByProperty($propertyId, $tenantId) as $tour) {
            foreach ($sceneModel->getByTour($tour['id'], $tenantId) as $scene) {
                $files[] = $scene['image_path'];
                $files[] = $scene['thumbnail_path'];
            }
        }

        $this->propertyModel->delete($propertyId);
        array_map('delete_upload', $files);

        flash('success', 'Property deleted successfully');
        redirect(base_url('?route=property'));
    }

    /**
     * Read property fields from the request
     */
    private function collectInput()
    {
        $currency = strtoupper(sanitize(post('currency')));

        return [
            'title' => sanitize(post('title')),
            'reference_code' => sanitize(post('reference_code')) ?: null,
            'type' => enum_value(post('type'), Property::TYPES, 'other'),
            'status' => enum_value(post('status'), Property::STATUSES, 'for_sale'),
            'price' => float_or_null(post('price')),
            'currency' => preg_match('/^[A-Z]{3}$/', $currency) ? $currency : config('default_currency'),
            'city' => sanitize(post('city')),
            'area' => sanitize(post('area')),
            'address' => sanitize(post('address')),
            'bedrooms' => int_or_null(post('bedrooms')),
            'bathrooms' => int_or_null(post('bathrooms')),
            'size' => float_or_null(post('size')),
            'description' => sanitize(post('description')),
        ];
    }

    private function validate(array $data)
    {
        $errors = [];
        if ($data['title'] === '') $errors[] = 'Title is required';
        if (mb_strlen($data['title']) > 255) $errors[] = 'Title is too long';
        foreach (['price', 'size', 'bedrooms', 'bathrooms'] as $field) {
            if ($data[$field] !== null && $data[$field] < 0) {
                $errors[] = humanize($field) . ' cannot be negative';
            }
        }
        return $errors;
    }

    private function uploadImage()
    {
        return upload_file($_FILES['main_image'], config('upload_path') . '/properties');
    }
}
