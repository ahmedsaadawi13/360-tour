<?php
/**
 * Application Configuration
 */

return [
    'name' => 'Splash360 Tour',
    'version' => '1.0.0',
    'base_url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
    'session_lifetime' => 7200, // 2 hours in seconds
    'upload_path' => __DIR__ . '/../public/uploads',
    'max_upload_size' => 10485760, // 10MB in bytes
    'allowed_image_types' => ['image/jpeg', 'image/jpg', 'image/png'],
    'default_currency' => 'USD',
    'trial_days' => 14,
    'support_email' => 'support@splash360tour.com',
];
