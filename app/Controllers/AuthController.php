<?php
/**
 * Authentication Controller
 *
 * Handles login, registration, password reset
 */

class AuthController
{
    const MIN_PASSWORD_LENGTH = 8;

    private $userModel;
    private $tenantModel;
    private $planModel;
    private $subscriptionModel;
    private $resetModel;
    private $attemptModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tenantModel = new Tenant();
        $this->planModel = new Plan();
        $this->subscriptionModel = new TenantSubscription();
        $this->resetModel = new PasswordReset();
        $this->attemptModel = new LoginAttempt();
    }

    /**
     * Show login page
     */
    public function login()
    {
        if (is_authenticated()) {
            redirect(base_url(has_role('platform_admin') ? '?route=admin/dashboard' : '?route=dashboard'));
        }

        if (!is_post()) {
            view('auth.login');
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Your session expired, please try again');
            redirect(base_url('?route=auth/login'));
        }

        $email = strtolower(sanitize(post('email')));
        $password = (string) post('password');

        if (empty($email) || $password === '') {
            flash('error', 'Email and password are required');
            view('auth.login');
            return;
        }

        if ($this->attemptModel->isLockedOut($email, client_ip())) {
            flash('error', 'Too many failed attempts. Please try again in ' . config('login_lockout_minutes') . ' minutes.');
            view('auth.login');
            return;
        }

        $user = $this->userModel->verifyPassword($email, $password);

        if (!$user || $user['status'] !== 'active') {
            $this->attemptModel->recordFailure($email, client_ip());
            flash('error', 'Invalid credentials or account inactive');
            view('auth.login');
            return;
        }

        if ($user['tenant_id']) {
            $tenant = $this->tenantModel->find($user['tenant_id']);
            if (!$tenant || $tenant['status'] !== 'active') {
                flash('error', 'Your company account is suspended. Please contact support.');
                view('auth.login');
                return;
            }
        }

        // Transparently upgrade old hashes
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->userModel->updatePassword($user['id'], $password);
        }

        $this->attemptModel->clear($email);
        login_user($user);
        $this->userModel->updateLastLogin($user['id']);

        flash('success', 'Welcome back!');
        redirect(base_url($user['role'] === 'platform_admin' ? '?route=admin/dashboard' : '?route=dashboard'));
    }

    /**
     * Show registration page
     */
    public function register()
    {
        if (is_authenticated()) {
            redirect(base_url('?route=dashboard'));
        }

        $plans = $this->planModel->getActivePlans();

        if (!is_post()) {
            view('auth.register', ['plans' => $plans, 'selectedPlan' => get_param('plan')]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Your session expired, please try again');
            redirect(base_url('?route=auth/register'));
        }

        $errors = [];

        $companyName = sanitize(post('company_name'));
        $email = strtolower(sanitize(post('email')));
        $firstName = sanitize(post('first_name'));
        $lastName = sanitize(post('last_name'));
        $password = (string) post('password');
        $passwordConfirm = (string) post('password_confirm');
        $planId = (int) post('plan_id');
        $plan = $planId ? $this->planModel->find($planId) : null;

        if ($companyName === '') $errors[] = 'Company name is required';
        if ($email === '' || !is_valid_email($email)) $errors[] = 'Valid email is required';
        if ($firstName === '') $errors[] = 'First name is required';
        if ($lastName === '') $errors[] = 'Last name is required';
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) $errors[] = 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters';
        if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match';
        if (!$plan || !$plan['is_active']) $errors[] = 'Please select a plan';

        if ($email !== '' && $this->userModel->findByEmail($email)) {
            $errors[] = 'Email already registered';
        }

        if (!empty($errors)) {
            flash('error', implode("\n", $errors));
            set_old_input($_POST);
            view('auth.register', ['plans' => $plans, 'selectedPlan' => $planId]);
            return;
        }

        try {
            $this->userModel->beginTransaction();

            $tenantId = $this->tenantModel->create([
                'name' => $companyName,
                'slug' => $this->tenantModel->generateUniqueSlug($companyName),
                'email' => $email,
                'status' => 'active'
            ]);

            $userId = $this->userModel->createUser([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'tenant_admin',
                'status' => 'active'
            ]);

            $this->subscriptionModel->createWithTrial($tenantId, $plan['id'], config('trial_days', 14));

            $this->userModel->commit();
        } catch (Throwable $e) {
            $this->userModel->rollBack();
            error_log('Registration failed: ' . $e->getMessage());
            flash('error', 'Could not create your account. Please try again.');
            set_old_input($_POST);
            view('auth.register', ['plans' => $plans, 'selectedPlan' => $planId]);
            return;
        }

        clear_old_input();

        Mailer::send(
            $email,
            'Welcome to ' . config('name'),
            "Hello {$firstName},\n\nYour {$plan['name']} trial has started and lasts " . config('trial_days') . " days.\n"
            . "Log in at " . base_url('?route=auth/login') . "\n\n" . config('name')
        );

        login_user($this->userModel->find($userId));

        flash('success', 'Account created successfully! Your trial period has started.');
        redirect(base_url('?route=dashboard'));
    }

    /**
     * Logout (POST only, to prevent CSRF logouts)
     */
    public function logout()
    {
        if (csrf_verify(post('csrf_token'))) {
            session_destroy_all();
            flash('success', 'Logged out successfully');
        }
        redirect(base_url('?route=auth/login'));
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword()
    {
        if (!is_post()) {
            view('auth.forgot_password');
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Your session expired, please try again');
            redirect(base_url('?route=auth/forgot-password'));
        }

        $email = strtolower(sanitize(post('email')));

        if ($email === '' || !is_valid_email($email)) {
            flash('error', 'Valid email is required');
            view('auth.forgot_password');
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $user['status'] === 'active') {
            $token = $this->resetModel->createToken($email, 1);
            $resetLink = base_url('?route=auth/reset-password&token=' . $token);

            Mailer::send(
                $email,
                'Reset your ' . config('name') . ' password',
                "Hello {$user['first_name']},\n\nUse the link below to choose a new password. It expires in 1 hour.\n\n"
                . "{$resetLink}\n\nIf you did not request this, you can ignore this email.\n\n" . config('name')
            );
        }

        // Same response whether or not the email exists (no account enumeration)
        flash('success', 'If that email is registered, a reset link has been sent.');
        redirect(base_url('?route=auth/forgot-password'));
    }

    /**
     * Show reset password page (also used to accept team invitations)
     */
    public function resetPassword()
    {
        $token = (string) (is_post() ? post('token') : get_param('token'));
        $reset = $this->resetModel->findValid($token);

        if (!$reset) {
            flash('error', 'Invalid or expired reset link');
            redirect(base_url('?route=auth/forgot-password'));
        }

        if (!is_post()) {
            view('auth.reset_password', ['token' => $token]);
            return;
        }

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Your session expired, please try again');
            redirect(base_url('?route=auth/reset-password&token=' . urlencode($token)));
        }

        $password = (string) post('password');
        $passwordConfirm = (string) post('password_confirm');

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            flash('error', 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters');
            view('auth.reset_password', ['token' => $token]);
            return;
        }

        if ($password !== $passwordConfirm) {
            flash('error', 'Passwords do not match');
            view('auth.reset_password', ['token' => $token]);
            return;
        }

        $user = $this->userModel->findByEmail($reset['email']);
        if ($user) {
            $this->userModel->updatePassword($user['id'], $password);
        }
        $this->resetModel->deleteForEmail($reset['email']);
        $this->attemptModel->clear($reset['email']);

        flash('success', 'Password updated. Please login.');
        redirect(base_url('?route=auth/login'));
    }
}
