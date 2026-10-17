<?php
/**
 * Global Helper Functions
 */

/**
 * Get an application config value (config/app.php), supports dot notation
 */
function config($key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/**
 * Whether the app runs in production mode
 */
function is_production()
{
    return config('env') === 'production';
}

/**
 * Start session if not already started
 */
function session_init()
{
    if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
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
 * Destroy the session and start a fresh one (so flash messages still work)
 */
function session_destroy_all()
{
    session_init();
    $_SESSION = [];
    session_regenerate_id(true);
}

/**
 * Log a user in: rotate the session id to prevent fixation
 */
function login_user($user)
{
    session_init();
    session_regenerate_id(true);
    unset($user['password']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    // Fresh CSRF token for the authenticated session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
 * Tenant admins manage billing, team and settings; agents manage content
 */
function is_tenant_admin()
{
    return has_role('tenant_admin');
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
 * Redirect back to the previous page (same host only) or a fallback
 */
function redirect_back($fallback)
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && parse_url($referer, PHP_URL_HOST) === parse_url(config('base_url'), PHP_URL_HOST)) {
        redirect($referer);
    }
    redirect($fallback);
}

/**
 * Get base URL
 */
function base_url($path = '')
{
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path)
{
    return base_url($path);
}

/**
 * Public URL of a published tour
 */
function tour_url($slug)
{
    return base_url('tour/view/' . rawurlencode($slug));
}

/**
 * Embeddable (iframe) URL of a published tour
 */
function tour_embed_url($slug)
{
    return base_url('tour/embed/' . rawurlencode($slug));
}

/**
 * Escape HTML output
 */
function e($string)
{
    return htmlspecialchars((string) ($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Encode data for safe embedding inside a <script> block
 */
function js_encode($data)
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
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
 * Hidden CSRF input field
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token
 */
function csrf_verify($token)
{
    session_init();
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Small inline POST form with a single button (used for delete/state actions)
 */
function post_button($url, $label, $class = 'btn btn-sm btn-danger', $confirm = null, $fields = [])
{
    $html = '<form method="POST" action="' . e($url) . '" class="inline-form"';
    if ($confirm) {
        $html .= ' onsubmit="return confirm(' . e(js_encode($confirm)) . ')"';
    }
    $html .= '>' . csrf_field();
    foreach ($fields as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    $html .= '<button type="submit" class="' . e($class) . '">' . e($label) . '</button></form>';
    return $html;
}

/**
 * Set flash message (messages are rendered escaped; use newlines for line breaks)
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
 * Set old input values (never stores passwords or tokens)
 */
function set_old_input($data)
{
    session_init();
    foreach ($data as $key => $value) {
        if (preg_match('/password|token/i', $key) || is_array($value)) {
            continue;
        }
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
 * Normalize text input: trims and strips tags.
 *
 * Values are stored raw and must be escaped on output with e().
 * (HTML-encoding at input time caused double-escaped data.)
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return trim(strip_tags((string) ($data ?? '')));
}

/**
 * Return $value if it is one of $allowed, otherwise $default
 */
function enum_value($value, array $allowed, $default = null)
{
    return in_array($value, $allowed, true) ? $value : $default;
}

/**
 * Nullable numeric input helpers
 */
function int_or_null($value)
{
    return ($value === null || $value === '') ? null : (int) $value;
}

function float_or_null($value)
{
    return ($value === null || $value === '') ? null : (float) $value;
}

/**
 * Validate email
 */
function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate an absolute http(s) URL (rejects javascript:, data:, etc.)
 */
function is_safe_url($url)
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

/**
 * Validate a #rrggbb color
 */
function is_hex_color($color)
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color);
}

/**
 * Generate random string
 */
function random_string($length = 32)
{
    return bin2hex(random_bytes((int) ceil($length / 2)));
}

/**
 * Best-effort client IP (REMOTE_ADDR only; proxies must be configured explicitly)
 */
function client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Format currency (output is HTML-safe)
 */
function format_currency($amount, $currency = 'USD')
{
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AED' => 'AED ',
    ];

    $currency = $currency ?: 'USD';
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return e($symbol . number_format((float) $amount, 2));
}

/**
 * Human readable plan limit
 */
function format_limit($limit)
{
    return (int) $limit >= 9999 ? 'Unlimited' : (string) (int) $limit;
}

/**
 * Format date
 */
function format_date($date, $format = 'Y-m-d H:i')
{
    if (!$date) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Human label for snake_case enum values
 */
function humanize($value)
{
    return ucfirst(str_replace('_', ' ', (string) $value));
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
    $text = preg_replace('~[^\pL\d]+~u', '-', (string) $text);
    $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = $converted !== false ? $converted : $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return substr($text, 0, 200);
}

/**
 * Check if request is POST
 */
function is_post()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Check if request is GET
 */
function is_get()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
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
        throw new RuntimeException("View not found: {$name}");
    }

    require $viewFile;
}

/**
 * Render a 404 page and stop
 */
function abort_404()
{
    http_response_code(404);
    view('errors.404');
    exit;
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
 * Human readable upload error
 */
function upload_error_message($code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds the server upload limit';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        default:
            return 'Upload error occurred';
    }
}

/**
 * Whether a file was submitted for the given input name
 */
function has_upload($name)
{
    return isset($_FILES[$name]) && ($_FILES[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Upload an image file safely.
 *
 * The stored extension is derived from the detected MIME type (never from the
 * client file name) and the content must be a decodable image.
 */
function upload_file($file, $destinationPath, $allowedTypes = [])
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => upload_error_message($file['error'])];
    }

    $maxSize = config('max_upload_size', 10485760);
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large (max ' . round($maxSize / 1048576) . 'MB)'];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $allowedTypes = $allowedTypes ?: config('allowed_image_types');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true) || !isset($extensions[$mimeType])) {
        return ['success' => false, 'message' => 'Invalid file type (allowed: JPG, PNG, WEBP)'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $fullPath = rtrim($destinationPath, '/') . '/' . $filename;

    if (!is_dir($destinationPath) && !mkdir($destinationPath, 0755, true)) {
        return ['success' => false, 'message' => 'Upload directory is not writable'];
    }

    $moved = is_uploaded_file($file['tmp_name'])
        ? move_uploaded_file($file['tmp_name'], $fullPath)
        : (PHP_SAPI === 'cli' && rename($file['tmp_name'], $fullPath));

    if (!$moved) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return [
        'success' => true,
        'filename' => $filename,
        'path' => $fullPath,
        'width' => $info[0],
        'height' => $info[1],
    ];
}

/**
 * Delete a previously uploaded file given its public path (e.g. /uploads/scenes/x.jpg)
 */
function delete_upload($publicPath)
{
    if (!$publicPath || strpos($publicPath, '/uploads/') !== 0 || strpos($publicPath, '..') !== false) {
        return;
    }
    $file = __DIR__ . '/../../public' . $publicPath;
    if (is_file($file)) {
        @unlink($file);
    }
}
