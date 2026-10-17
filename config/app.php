<?php
/**
 * Application Configuration
 *
 * Values can be overridden through environment variables (see .env.example).
 */

return [
    'name' => 'Splash360 Tour',
    'version' => '2.0.0',
    'env' => getenv('APP_ENV') ?: 'production',
    'base_url' => getenv('APP_URL') ?: 'http://localhost',
    // Secret used for hashing visitor fingerprints and similar values
    'app_key' => getenv('APP_KEY') ?: 'change-me-in-env',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    'session_lifetime' => 7200, // 2 hours in seconds
    'upload_path' => __DIR__ . '/../public/uploads',
    'max_upload_size' => (int) (getenv('MAX_UPLOAD_SIZE') ?: 20971520), // 20MB in bytes
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'default_currency' => 'USD',
    'currencies' => ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'EGP', 'QAR', 'KWD'],
    'trial_days' => 14,
    'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@splash360tour.com',

    // Login throttling
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,

    // Mail: driver "log" writes to storage/logs/mail.log, "mail" uses PHP mail()
    'mail' => [
        'driver' => getenv('MAIL_DRIVER') ?: 'log',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@splash360tour.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Splash360 Tour',
    ],

    // Payment gateway: "dummy" approves every charge (replace for production)
    'payment_gateway' => getenv('PAYMENT_GATEWAY') ?: 'dummy',
];
