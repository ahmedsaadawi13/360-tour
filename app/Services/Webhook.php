<?php
/**
 * Webhook Service
 *
 * Sends tenant events (e.g. lead.created) as JSON to the tenant's configured
 * webhook URL, so leads can flow into n8n, a CRM (vTiger, HubSpot...) or Zapier.
 *
 * Payload: {"event": "...", "sent_at": "...", "tenant": {...}, "data": {...}}
 * Header X-Splash360-Signature: sha256 HMAC of the body using APP_KEY.
 */

class Webhook
{
    public static function dispatch($tenant, $event, array $data)
    {
        $url = $tenant['webhook_url'] ?? '';
        if (!$url || !is_safe_url($url) || !function_exists('curl_init')) {
            return false;
        }

        $body = json_encode([
            'event' => $event,
            'sent_at' => date('c'),
            'tenant' => ['id' => (int) $tenant['id'], 'name' => $tenant['name'], 'slug' => $tenant['slug']],
            'data' => $data,
        ]);

        // SSRF protection: resolve once, reject private/reserved targets and pin the IP
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: (parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80);
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!self::isAllowedIp($ip)) {
            error_log("Webhook {$event}: refused non-public target {$host} ({$ip})");
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Splash360-Webhook/1.0',
                'X-Splash360-Event: ' . $event,
                'X-Splash360-Signature: sha256=' . hash_hmac('sha256', $body, config('app_key')),
            ],
        ]);

        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            error_log("Webhook {$event} to {$url} failed: HTTP {$status} {$error}");
            return false;
        }
        return true;
    }

    /**
     * Public IPs only, unless WEBHOOK_ALLOW_PRIVATE=1 (self-hosted n8n on the LAN)
     */
    private static function isAllowedIp($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (getenv('WEBHOOK_ALLOW_PRIVATE') === '1') {
            return true;
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
