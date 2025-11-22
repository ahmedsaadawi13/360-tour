<?php
/**
 * Property Controller
 *
 * Handles property CRUD operations
 */

require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Models/Property.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';

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
            'type' => get_param('type'),
            'status' => get_param('status'),
            'city' => get_param('city'),
            'search' => get_param('search')
        ];

        $properties = $this->propertyModel->getByTenantFiltered($tenantId, $filters);

        view('property.index', ['properties' => $properties, 'filters' => $filters]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $tenantId = current_tenant_id();

        // Check quota
        $currentCount = $this->propertyModel->count(['tenant_id' => $tenantId]);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'properties', $currentCount)) {
            flash('error', 'Property limit reached. Please upgrade your plan.');
            redirect(base_url('?route=property'));
        }

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=property/create'));
            }

            $data = [
                'tenant_id' => $tenantId,
                'title' => sanitize(post('title')),
                'reference_code' => sanitize(post('reference_code')),
                'type' => sanitize(post('type')),
                'status' => sanitize(post('status')),
                'price' => post('price') ? (float) post('price') : null,
                'currency' => sanitize(post('currency')),
                'city' => sanitize(post('city')),
                'area' => sanitize(post('area')),
                'address' => sanitize(post('address')),
                'bedrooms' => post('bedrooms') ? (int) post('bedrooms') : null,
                'bathrooms' => post('bathrooms') ? (int) post('bathrooms') : null,
                'size' => post('size') ? (float) post('size') : null,
                'description' => sanitize(post('description')),
                'created_by' => session_get('user_id')
            ];

            // Handle image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = upload_file(
                    $_FILES['main_image'],
                    __DIR__ . '/../../public/uploads/properties',
                    ['image/jpeg', 'image/jpg', 'image/png']
                );

                if ($uploadResult['success']) {
                    $data['main_image'] = '/uploads/properties/' . $uploadResult['filename'];
                }
            }

            $propertyId = $this->propertyModel->create($data);

            flash('success', 'Property created successfully');
            redirect(base_url('?route=property/view&id=' . $propertyId));
        } else {
            view('property.create');
        }
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

        view('property.view', ['property' => $property]);
    }

    /**
     * Show edit form
     */
    public function edit()
    {
        $tenantId = current_tenant_id();
        $propertyId = (int) get_param('id');

        $property = $this->propertyModel->first(['id' => $propertyId, 'tenant_id' => $tenantId]);

        if (!$property) {
            flash('error', 'Property not found');
            redirect(base_url('?route=property'));
        }

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=property/edit&id=' . $propertyId));
            }

            $data = [
                'title' => sanitize(post('title')),
                'reference_code' => sanitize(post('reference_code')),
                'type' => sanitize(post('type')),
                'status' => sanitize(post('status')),
                'price' => post('price') ? (float) post('price') : null,
                'currency' => sanitize(post('currency')),
                'city' => sanitize(post('city')),
                'area' => sanitize(post('area')),
                'address' => sanitize(post('address')),
                'bedrooms' => post('bedrooms') ? (int) post('bedrooms') : null,
                'bathrooms' => post('bathrooms') ? (int) post('bathrooms') : null,
                'size' => post('size') ? (float) post('size') : null,
                'description' => sanitize(post('description'))
            ];

            // Handle image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = upload_file(
                    $_FILES['main_image'],
                    __DIR__ . '/../../public/uploads/properties',
                    ['image/jpeg', 'image/jpg', 'image/png']
                );

                if ($uploadResult['success']) {
                    $data['main_image'] = '/uploads/properties/' . $uploadResult['filename'];
                }
            }

            $this->propertyModel->update($propertyId, $data);

            flash('success', 'Property updated successfully');
            redirect(base_url('?route=property/view&id=' . $propertyId));
        } else {
            view('property.edit', ['property' => $property]);
        }
    }

    /**
     * Delete property
     */
    public function delete()
    {
        $tenantId = current_tenant_id();
        $propertyId = (int) get_param('id');

        if (!csrf_verify(get_param('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=property'));
        }

        $property = $this->propertyModel->first(['id' => $propertyId, 'tenant_id' => $tenantId]);

        if ($property) {
            $this->propertyModel->delete($propertyId);
            flash('success', 'Property deleted successfully');
        } else {
            flash('error', 'Property not found');
        }

        redirect(base_url('?route=property'));
    }
}
