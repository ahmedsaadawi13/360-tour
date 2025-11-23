<?php
/**
 * Installation Verification Script
 *
 * Run this script to verify your Splash360 Tour installation
 * Access via: http://localhost/verify.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$checks = [];
$errors = [];
$warnings = [];

// Check PHP version
$phpVersion = phpversion();
$checks['PHP Version'] = $phpVersion;
if (version_compare($phpVersion, '7.4.0', '>=')) {
    $checks['PHP Version Status'] = '✓ OK';
} else {
    $errors[] = 'PHP 7.4.0 or higher is required';
    $checks['PHP Version Status'] = '✗ FAIL';
}

// Check PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'session', 'json'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $checks["PHP Extension: $ext"] = '✓ Loaded';
    } else {
        $errors[] = "Required PHP extension missing: $ext";
        $checks["PHP Extension: $ext"] = '✗ Missing';
    }
}

// Check directory permissions
$uploadDir = __DIR__ . '/public/uploads';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        $checks['Uploads Directory'] = '✓ Writable';
    } else {
        $errors[] = 'Uploads directory is not writable';
        $checks['Uploads Directory'] = '✗ Not Writable';
    }
} else {
    $errors[] = 'Uploads directory does not exist';
    $checks['Uploads Directory'] = '✗ Missing';
}

// Check .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $checks['.env File'] = '✓ Exists';
} else {
    $warnings[] = '.env file not found. Using .env.example as fallback.';
    $checks['.env File'] = '⚠ Missing';
}

// Check database configuration
if (file_exists(__DIR__ . '/config/database.php')) {
    $checks['Database Config'] = '✓ Found';
} else {
    $errors[] = 'Database configuration file missing';
    $checks['Database Config'] = '✗ Missing';
}

// Try database connection
try {
    require_once __DIR__ . '/config/database.php';
    $dbConfig = include __DIR__ . '/config/database.php';

    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database'],
        $dbConfig['charset']
    );

    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    $checks['Database Connection'] = '✓ Connected';

    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredTables = ['users', 'tenants', 'properties', 'tours', 'scenes', 'plans'];
    $missingTables = array_diff($requiredTables, $tables);

    if (empty($missingTables)) {
        $checks['Database Tables'] = '✓ All present (' . count($tables) . ' tables)';
    } else {
        $errors[] = 'Missing database tables: ' . implode(', ', $missingTables);
        $checks['Database Tables'] = '✗ Missing tables';
    }

    // Check for admin user
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'platform_admin'");
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        $checks['Platform Admin'] = '✓ Exists';
    } else {
        $warnings[] = 'No platform admin user found';
        $checks['Platform Admin'] = '⚠ No admin';
    }

} catch (PDOException $e) {
    $errors[] = 'Database connection failed: ' . $e->getMessage();
    $checks['Database Connection'] = '✗ Failed';
}

// Check mod_rewrite (Apache)
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        $checks['Apache mod_rewrite'] = '✓ Enabled';
    } else {
        $warnings[] = 'mod_rewrite not detected. URL routing may not work.';
        $checks['Apache mod_rewrite'] = '⚠ Not detected';
    }
} else {
    $checks['Apache mod_rewrite'] = 'ℹ Cannot detect (not Apache or CLI)';
}

// Overall status
$status = empty($errors) ? 'PASS' : 'FAIL';
$statusColor = $status === 'PASS' ? 'green' : 'red';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splash360 Tour - Installation Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .status {
            font-size: 24px;
            font-weight: bold;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        .status.pass {
            background: #d4edda;
            color: #155724;
        }
        .status.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .check-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .next-steps {
            margin-top: 30px;
            padding: 20px;
            background: #e7f3ff;
            border-radius: 5px;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .next-steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Splash360 Tour Installation Verification</h1>
        <p>This script checks if your installation is properly configured.</p>

        <div class="status <?php echo strtolower($status); ?>">
            Overall Status: <?php echo $status; ?>
        </div>

        <?php if (!empty($errors)): ?>
            <h3>❌ Errors (<?php echo count($errors); ?>)</h3>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
            <h3>⚠️ Warnings (<?php echo count($warnings); ?>)</h3>
            <?php foreach ($warnings as $warning): ?>
                <div class="warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3>System Checks</h3>
        <?php foreach ($checks as $name => $result): ?>
            <div class="check-item">
                <span><?php echo htmlspecialchars($name); ?></span>
                <strong><?php echo htmlspecialchars($result); ?></strong>
            </div>
        <?php endforeach; ?>

        <?php if ($status === 'PASS'): ?>
            <div class="next-steps">
                <h3>✅ Ready to Go!</h3>
                <p>Your Splash360 Tour installation is properly configured.</p>
                <ol>
                    <li>Access the application: <a href="/">Go to Homepage</a></li>
                    <li>Login as Platform Admin:
                        <ul>
                            <li>Email: <code>admin@splash360tour.com</code></li>
                            <li>Password: <code>admin123</code></li>
                        </ul>
                    </li>
                    <li><strong>Important:</strong> Change the admin password immediately!</li>
                    <li>Delete this <code>verify.php</code> file for security</li>
                </ol>
                <a href="/?route=auth/login" class="btn">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="info">
                <h3>🔧 Fix Required Issues</h3>
                <p>Please resolve the errors above before using the application.</p>
                <p>Common solutions:</p>
                <ul>
                    <li><strong>Database errors:</strong> Check your <code>.env</code> file and ensure MySQL is running</li>
                    <li><strong>Missing tables:</strong> Import <code>database.sql</code>: <code>mysql -u root -p splash360_tour < database.sql</code></li>
                    <li><strong>Permission errors:</strong> Run: <code>chmod -R 775 public/uploads</code></li>
                    <li><strong>Missing extensions:</strong> Install required PHP extensions via your package manager</li>
                </ul>
                <p>Then refresh this page to verify again.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
            <p>Splash360 Tour v1.0.0 | PHP <?php echo $phpVersion; ?> | <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
