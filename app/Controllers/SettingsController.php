<?php
/**
 * Settings Controller
 *
 * Company profile & branding (tenant admins) and personal profile (everyone)
 */

class SettingsController
{
    private $tenantModel;
    private $userModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->userModel = new User();
    }

    /**
     * Company profile, branding and integrations
     */
    public function company()
    {
        TenantAdminMiddleware::handle();
        $tenantId = current_tenant_id();
        $tenant = $this->tenantModel->find($tenantId);

        if (!is_post()) {
            view('settings.company', ['tenant' => $tenant]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=settings/company'));
        }

        if (post('action') === 'test_webhook') {
            $ok = Webhook::dispatch($tenant, 'webhook.test', ['message' => 'Test event from ' . config('name')]);
            flash($ok ? 'success' : 'error', $ok ? 'Test event delivered.' : 'Webhook delivery failed. Check the URL and that it accepts POST requests.');
            redirect(base_url('?route=settings/company'));
        }

        $data = [
            'name' => sanitize(post('name')),
            'email' => strtolower(sanitize(post('email'))),
            'phone' => sanitize(post('phone')) ?: null,
            'website' => trim((string) post('website')) ?: null,
            'address' => sanitize(post('address')) ?: null,
            'brand_color' => post('brand_color') ?: null,
            'webhook_url' => trim((string) post('webhook_url')) ?: null,
        ];

        $errors = [];
        if ($data['name'] === '') $errors[] = 'Company name is required';
        if (!is_valid_email($data['email'])) $errors[] = 'Valid company email is required';
        if ($data['website'] && !is_safe_url($data['website'])) $errors[] = 'Website must be a valid http(s) URL';
        if ($data['webhook_url'] && !is_safe_url($data['webhook_url'])) $errors[] = 'Webhook URL must be a valid http(s) URL';
        if ($data['brand_color'] && !is_hex_color($data['brand_color'])) $errors[] = 'Brand color must be a hex color like #3498db';

        if (!$errors && has_upload('logo')) {
            $upload = upload_file($_FILES['logo'], config('upload_path') . '/logos');
            if ($upload['success']) {
                $data['logo'] = '/uploads/logos/' . $upload['filename'];
            } else {
                $errors[] = 'Logo: ' . $upload['message'];
            }
        }

        if (post('remove_logo')) {
            $data['logo'] = null;
        }

        if ($errors) {
            flash('error', implode("\n", $errors));
            view('settings.company', ['tenant' => array_merge($tenant, $data)]);
            return;
        }

        $this->tenantModel->update($tenantId, $data);

        if (array_key_exists('logo', $data) && $tenant['logo']) {
            delete_upload($tenant['logo']);
        }

        flash('success', 'Company settings saved');
        redirect(base_url('?route=settings/company'));
    }

    /**
     * Personal profile and password change
     */
    public function profile()
    {
        AuthMiddleware::handle();
        $user = $this->userModel->find(session_get('user_id'));

        if (!is_post()) {
            view('settings.profile', ['profile' => $user]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=settings/profile'));
        }

        if (post('action') === 'password') {
            $current = (string) post('current_password');
            $new = (string) post('new_password');

            if (!password_verify($current, $user['password'])) {
                flash('error', 'Current password is incorrect');
            } elseif (strlen($new) < AuthController::MIN_PASSWORD_LENGTH) {
                flash('error', 'New password must be at least ' . AuthController::MIN_PASSWORD_LENGTH . ' characters');
            } elseif ($new !== post('new_password_confirm')) {
                flash('error', 'New passwords do not match');
            } else {
                $this->userModel->updatePassword($user['id'], $new);
                session_regenerate_id(true);
                flash('success', 'Password changed');
            }
            redirect(base_url('?route=settings/profile'));
        }

        $firstName = sanitize(post('first_name'));
        $lastName = sanitize(post('last_name'));

        if ($firstName === '' || $lastName === '') {
            flash('error', 'First and last name are required');
        } else {
            $this->userModel->update($user['id'], ['first_name' => $firstName, 'last_name' => $lastName]);
            flash('success', 'Profile updated');
        }

        redirect(base_url('?route=settings/profile'));
    }
}
