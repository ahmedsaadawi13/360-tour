<?php
/**
 * Scheduled maintenance. Run hourly, e.g.:
 *   0 * * * * php /var/www/html/bin/cron.php >> /var/www/html/storage/logs/cron.log 2>&1
 *
 * - Expires ended trials, ends canceled subscriptions at period end
 * - Renews active subscriptions (invoice + charge), marks failures past_due
 * - Cleans up expired password reset tokens and old login attempts
 */

require_once __DIR__ . '/../app/bootstrap.php';

$summary = (new BillingService())->processRenewals();

$db = Database::getInstance()->getConnection();
$db->exec("DELETE FROM password_resets WHERE expires_at IS NULL OR expires_at < NOW()");
$db->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

echo date('c') . ' ' . json_encode($summary) . "\n";
