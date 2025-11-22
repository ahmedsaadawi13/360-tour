<?php
/**
 * Authentication Controller
 *
 * Handles login, registration, password reset
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Tenant.php';
require_once __DIR__ . '/../Models/Plan.php';
require_once __DIR__ . '/../Models/TenantSubscription.php';

class AuthController
{
    private $userModel;
    private $tenantModel;
    private $planModel;
    private $subscriptionModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tenantModel = new Tenant();
        $this->planModel = new Plan();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * Show login page
     */
    public function login()
    {
        if (is_authenticated()) {
            redirect(base_url('?route=dashboard'));
        }

        if (is_post()) {
            $email = sanitize(post('email'));
            $password = post('password');

            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=auth/login'));
            }

            if (empty($email) || empty($password)) {
                flash('error', 'Email and password are required');
                view('auth.login');
                return;
            }

            $user = $this->userModel->verifyPassword($email, $password);

            if ($user && $user['status'] === 'active') {
                session_set('user_id', $user['id']);
                session_set('user', $user);
                $this->userModel->updateLastLogin($user['id']);

                flash('success', 'Welcome back!');

                if ($user['role'] === 'platform_admin') {
                    redirect(base_url('?route=admin/dashboard'));
                } else {
                    redirect(base_url('?route=dashboard'));
                }
            } else {
                flash('error', 'Invalid credentials or account inactive');
                view('auth.login');
            }
        } else {
            view('auth.login');
        }
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

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=auth/register'));
            }

            // Validate input
            $errors = [];

            $companyName = sanitize(post('company_name'));
            $email = sanitize(post('email'));
            $firstName = sanitize(post('first_name'));
            $lastName = sanitize(post('last_name'));
            $password = post('password');
            $passwordConfirm = post('password_confirm');
            $planId = (int) post('plan_id');

            if (empty($companyName)) $errors[] = 'Company name is required';
            if (empty($email) || !is_valid_email($email)) $errors[] = 'Valid email is required';
            if (empty($firstName)) $errors[] = 'First name is required';
            if (empty($lastName)) $errors[] = 'Last name is required';
            if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
            if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match';
            if (!$planId) $errors[] = 'Please select a plan';

            // Check if email already exists
            if ($this->userModel->findByEmail($email)) {
                $errors[] = 'Email already registered';
            }

            if (!empty($errors)) {
                flash('error', implode('<br>', $errors));
                set_old_input($_POST);
                view('auth.register', ['plans' => $plans]);
                return;
            }

            // Create tenant
            $tenantSlug = slugify($companyName);
            $originalSlug = $tenantSlug;
            $counter = 1;

            while ($this->tenantModel->findBySlug($tenantSlug)) {
                $tenantSlug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $tenantId = $this->tenantModel->create([
                'name' => $companyName,
                'slug' => $tenantSlug,
                'email' => $email,
                'status' => 'active'
            ]);

            // Create user
            $userId = $this->userModel->createUser([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'tenant_admin',
                'status' => 'active'
            ]);

            // Create subscription with trial
            $config = require __DIR__ . '/../../config/app.php';
            $trialDays = $config['trial_days'];
            $this->subscriptionModel->createWithTrial($tenantId, $planId, $trialDays);

            // Auto login
            $user = $this->userModel->find($userId);
            session_set('user_id', $user['id']);
            session_set('user', $user);

            flash('success', 'Account created successfully! Your trial period has started.');
            redirect(base_url('?route=dashboard'));
        } else {
            view('auth.register', ['plans' => $plans]);
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        session_destroy_all();
        flash('success', 'Logged out successfully');
        redirect(base_url('?route=auth/login'));
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword()
    {
        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=auth/forgot-password'));
            }

            $email = sanitize(post('email'));

            if (empty($email) || !is_valid_email($email)) {
                flash('error', 'Valid email is required');
                view('auth.forgot_password');
                return;
            }

            $user = $this->userModel->findByEmail($email);

            if ($user) {
                $token = random_string(64);
                $db = Database::getInstance()->getConnection();

                // Delete old tokens for this email
                $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);

                // Create new token
                $stmt = $db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
                $stmt->execute([$email, $token]);

                // In production, send email here
                // For now, just flash the reset link
                $resetLink = base_url("?route=auth/reset-password&token={$token}");

                flash('success', "Password reset link: <a href='{$resetLink}'>{$resetLink}</a>");
            } else {
                // Don't reveal if email exists
                flash('success', 'If that email is registered, a reset link has been sent.');
            }

            view('auth.forgot_password');
        } else {
            view('auth.forgot_password');
        }
    }

    /**
     * Show reset password page
     */
    public function resetPassword()
    {
        $token = get_param('token');

        if (!$token) {
            flash('error', 'Invalid reset token');
            redirect(base_url('?route=auth/login'));
        }

        $db = Database::getInstance()->getConnection();

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=auth/reset-password&token=' . $token));
            }

            $password = post('password');
            $passwordConfirm = post('password_confirm');

            if (empty($password) || strlen($password) < 6) {
                flash('error', 'Password must be at least 6 characters');
                view('auth.reset_password', ['token' => $token]);
                return;
            }

            if ($password !== $passwordConfirm) {
                flash('error', 'Passwords do not match');
                view('auth.reset_password', ['token' => $token]);
                return;
            }

            // Verify token (valid for 1 hour)
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                flash('error', 'Invalid or expired reset token');
                redirect(base_url('?route=auth/login'));
            }

            // Update password
            $user = $this->userModel->findByEmail($reset['email']);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);

            // Delete token
            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            flash('success', 'Password reset successfully. Please login.');
            redirect(base_url('?route=auth/login'));
        } else {
            // Verify token exists
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                flash('error', 'Invalid or expired reset token');
                redirect(base_url('?route=auth/login'));
            }

            view('auth.reset_password', ['token' => $token]);
        }
    }
}
