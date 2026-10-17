<?php
/**
 * Application Bootstrap
 *
 * Loads environment variables, configures error handling and sessions,
 * and registers the class autoloader. Shared by the web front controller
 * and the CLI scripts in bin/.
 */

define('BASE_PATH', dirname(__DIR__));

/**
 * Load KEY=VALUE pairs from .env into the environment.
 * Real environment variables (e.g. from docker-compose) take precedence.
 */
function load_env_file($path)
{
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

load_env_file(BASE_PATH . '/.env');

require_once BASE_PATH . '/app/Helpers/functions.php';
require_once BASE_PATH . '/app/Database.php';
require_once BASE_PATH . '/app/Router.php';

date_default_timezone_set(config('timezone', 'UTC'));

// Error reporting: never display errors outside development
error_reporting(E_ALL);
ini_set('display_errors', is_production() ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

// Autoload models, services, middleware and controllers by class name
spl_autoload_register(function ($class) {
    foreach (['Models', 'Services', 'Middleware', 'Controllers'] as $dir) {
        $file = BASE_PATH . "/app/{$dir}/{$class}.php";
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

/**
 * Start a hardened session (web requests only)
 */
function start_secure_session()
{
    if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strpos(config('base_url'), 'https://') === 0;

    session_name('splash360_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) config('session_lifetime', 7200));
    session_start();

    // Expire idle sessions
    $now = time();
    $last = $_SESSION['_last_activity'] ?? $now;
    if ($now - $last > config('session_lifetime', 7200)) {
        session_unset();
        session_regenerate_id(true);
    }
    $_SESSION['_last_activity'] = $now;
}
