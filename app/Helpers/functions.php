<?php
/**
 * Global Helper Functions
 */

/**
 * Start session if not already started
 */
function session_init()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Get session value
 */
function session_get($key, $default = null)
{
    session_init();
    return $_SESSION[$key] ?? $default;
}

/**
 * Set session value
 */
function session_set($key, $value)
{
    session_init();
    $_SESSION[$key] = $value;
}

/**
 * Delete session key
 */
function session_delete($key)
{
    session_init();
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Destroy entire session
 */
function session_destroy_all()
{
    session_init();
    session_destroy();
}

/**
 * Check if user is authenticated
 */
function is_authenticated()
{
    return session_get('user_id') !== null;
}

/**
 * Get current authenticated user
 */
function current_user()
{
    return session_get('user');
}

/**
 * Get current tenant ID
 */
function current_tenant_id()
{
    $user = current_user();
    return $user['tenant_id'] ?? null;
}

/**
 * Check if user has specific role
 */
function has_role($role)
{
    $user = current_user();
    return $user && $user['role'] === $role;
}

/**
 * Redirect to URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Get base URL
 */
function base_url($path = '')
{
    $config = require __DIR__ . '/../../config/app.php';
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path)
{
    return base_url($path);
}

/**
 * Escape HTML output
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token()
{
    session_init();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function csrf_verify($token)
{
    session_init();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function flash($key, $message = null)
{
    session_init();
    if ($message === null) {
        $msg = $_SESSION["flash_{$key}"] ?? null;
        unset($_SESSION["flash_{$key}"]);
        return $msg;
    }
    $_SESSION["flash_{$key}"] = $message;
}

/**
 * Get old input value (for form repopulation)
 */
function old($key, $default = '')
{
    return session_get("old_{$key}", $default);
}

/**
 * Set old input values
 */
function set_old_input($data)
{
    session_init();
    foreach ($data as $key => $value) {
        $_SESSION["old_{$key}"] = $value;
    }
}

/**
 * Clear old input values
 */
function clear_old_input()
{
    session_init();
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'old_') === 0) {
            unset($_SESSION[$key]);
        }
    }
}

/**
 * Sanitize input
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function random_string($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'USD')
{
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AED' => 'AED ',
    ];

    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'Y-m-d H:i:s')
{
    if (!$date) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get file extension
 */
function get_extension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate slug from string
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

/**
 * Check if request is POST
 */
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function is_get()
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get POST data
 */
function post($key = null, $default = null)
{
    if ($key === null) {
        return $_POST;
    }
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function get_param($key = null, $default = null)
{
    if ($key === null) {
        return $_GET;
    }
    return $_GET[$key] ?? $default;
}

/**
 * Display view
 */
function view($name, $data = [])
{
    extract($data);
    $viewFile = __DIR__ . '/../../views/' . str_replace('.', '/', $name) . '.php';

    if (!file_exists($viewFile)) {
        die("View not found: {$name}");
    }

    require $viewFile;
}

/**
 * Return JSON response
 */
function json_response($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Upload file
 */
function upload_file($file, $destinationPath, $allowedTypes = [])
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }

    if ($file['size'] > 10485760) { // 10MB
        return ['success' => false, 'message' => 'File too large (max 10MB)'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $extension = get_extension($file['name']);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $fullPath = rtrim($destinationPath, '/') . '/' . $filename;

    if (!is_dir($destinationPath)) {
        mkdir($destinationPath, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => $fullPath];
}
