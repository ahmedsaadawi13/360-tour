<?php
/**
 * User Controller
 *
 * Team management for tenant admins: invite agents/admins, change roles,
 * deactivate and remove users. Enforces the plan's max_users quota.
 */

class UserController
{
    const INVITE_VALID_HOURS = 72;

    private $userModel;
    private $subscriptionModel;

    public function __construct()
    {
        TenantAdminMiddleware::handle();
        $this->userModel = new User();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * List team members
     */
    public function index()
    {
        $tenantId = current_tenant_id();

        view('team.index', [
            'users' => $this->userModel->getByTenant($tenantId),
            'subscription' => $this->subscriptionModel->getActiveSubscription($tenantId),
        ]);
    }

    /**
     * Invite a new team member (they receive a link to set their password)
     */
    public function create()
    {
        $tenantId = current_tenant_id();

        $currentCount = $this->userModel->count(['tenant_id' => $tenantId]);
        if (!$this->subscriptionModel->checkQuota($tenantId, 'users', $currentCount)) {
            flash('error', 'User limit reached for your plan. Please upgrade to add more team members.');
            redirect(base_url('?route=team'));
        }

        if (!is_post()) {
            view('team.create');
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=team/create'));
        }

        $email = strtolower(sanitize(post('email')));
        $firstName = sanitize(post('first_name'));
        $lastName = sanitize(post('last_name'));
        $role = enum_value(post('role'), User::TENANT_ROLES, 'agent');

        $errors = [];
        if (!is_valid_email($email)) $errors[] = 'Valid email is required';
        if ($firstName === '' || $lastName === '') $errors[] = 'First and last name are required';
        if ($email && $this->userModel->findByEmail($email)) $errors[] = 'A user with this email already exists';

        if ($errors) {
            flash('error', implode("\n", $errors));
            set_old_input($_POST);
            view('team.create');
            return;
        }

        clear_old_input();

        // Random unusable password until the invitation is accepted
        $this->userModel->createUser([
            'tenant_id' => $tenantId,
            'email' => $email,
            'password' => random_string(48),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'status' => 'active',
        ]);

        $this->sendInvitation($email, $firstName);

        flash('success', "Invitation sent to {$email}");
        redirect(base_url('?route=team'));
    }

    /**
     * Edit role/status, or resend invitation
     */
    public function edit()
    {
        $tenantId = current_tenant_id();
        $userId = (int) get_param('id');
        $user = $this->userModel->findForTenant($userId, $tenantId);

        if (!$user) {
            flash('error', 'User not found');
            redirect(base_url('?route=team'));
        }

        if (!is_post()) {
            view('team.edit', ['member' => $user]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=team/edit&id=' . $userId));
        }

        if (post('action') === 'resend_invite') {
            $this->sendInvitation($user['email'], $user['first_name']);
            flash('success', 'Invitation re-sent to ' . $user['email']);
            redirect(base_url('?route=team'));
        }

        $role = enum_value(post('role'), User::TENANT_ROLES, $user['role']);
        $status = enum_value(post('status'), User::STATUSES, $user['status']);

        $removesAdmin = $user['role'] === 'tenant_admin' && $user['status'] === 'active'
            && ($role !== 'tenant_admin' || $status !== 'active');

        if ($removesAdmin && $this->userModel->countActiveAdmins($tenantId) <= 1) {
            flash('error', 'Your company needs at least one active administrator.');
            redirect(base_url('?route=team/edit&id=' . $userId));
        }

        $this->userModel->update($userId, [
            'first_name' => sanitize(post('first_name')) ?: $user['first_name'],
            'last_name' => sanitize(post('last_name')) ?: $user['last_name'],
            'role' => $role,
            'status' => $status,
        ]);

        flash('success', 'Team member updated');
        redirect(base_url('?route=team'));
    }

    /**
     * Remove a team member
     */
    public function delete()
    {
        $tenantId = current_tenant_id();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=team'));
        }

        $user = $this->userModel->findForTenant((int) post('id'), $tenantId);

        if (!$user) {
            flash('error', 'User not found');
        } elseif ((int) $user['id'] === (int) session_get('user_id')) {
            flash('error', 'You cannot remove your own account');
        } elseif ($user['role'] === 'tenant_admin' && $this->userModel->countActiveAdmins($tenantId) <= 1) {
            flash('error', 'Your company needs at least one active administrator.');
        } else {
            $this->userModel->delete($user['id']);
            (new PasswordReset())->deleteForEmail($user['email']);
            flash('success', 'Team member removed');
        }

        redirect(base_url('?route=team'));
    }

    private function sendInvitation($email, $firstName)
    {
        $inviter = current_user();
        $tenant = (new Tenant())->find(current_tenant_id());
        $token = (new PasswordReset())->createToken($email, self::INVITE_VALID_HOURS);

        Mailer::send(
            $email,
            "You're invited to {$tenant['name']} on " . config('name'),
            "Hello {$firstName},\n\n{$inviter['first_name']} {$inviter['last_name']} invited you to join {$tenant['name']} on " . config('name') . ".\n\n"
            . "Set your password to get started (link valid for " . self::INVITE_VALID_HOURS . " hours):\n"
            . base_url('?route=auth/reset-password&token=' . $token) . "\n\n" . config('name')
        );
    }
}
