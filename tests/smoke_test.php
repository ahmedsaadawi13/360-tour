<?php
/**
 * End-to-end smoke test.
 *
 * Runs real HTTP requests against a running instance backed by a FRESH
 * database (it registers tenants, uploads files and suspends a tenant).
 *
 *   mysql splash360_test < database.sql
 *   DB_NAME=splash360_test APP_URL=http://127.0.0.1:8099 APP_ENV=development \
 *       php -S 127.0.0.1:8099 -t public public/index.php &
 *   DB_NAME=splash360_test APP_URL=http://127.0.0.1:8099 php tests/smoke_test.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

$baseUrl = rtrim(config('base_url'), '/');
$failures = 0;
$passes = 0;
$tmpDir = sys_get_temp_dir() . '/splash360_smoke_' . getmypid();
@mkdir($tmpDir);

function check($condition, $label)
{
    global $failures, $passes;
    if ($condition) {
        $passes++;
        echo "  \033[32m✓\033[0m {$label}\n";
    } else {
        $failures++;
        echo "  \033[31m✗ {$label}\033[0m\n";
    }
}

/**
 * Minimal browser: keeps cookies, returns status/headers/body/redirect location
 */
class Client
{
    private $jar;
    public $last;

    public function __construct($name)
    {
        global $tmpDir;
        $this->jar = "{$tmpDir}/{$name}.cookies";
    }

    public function request($method, $path, $fields = null, $files = [])
    {
        global $baseUrl;
        $url = preg_match('#^https?://#', $path) ? $path : $baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        $headers = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 SmokeTest',
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                if (strpos($line, ':') !== false) {
                    list($k, $v) = explode(':', $line, 2);
                    $headers[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($method === 'POST') {
            $post = $fields ?: [];
            foreach ($files as $name => $file) {
                $post[$name] = new CURLFile($file['path'], $file['type'], $file['name']);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $files ? $post : http_build_query($post));
        }
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->last = ['status' => $status, 'headers' => $headers, 'body' => (string) $body,
            'location' => $headers['location'] ?? null];
    }

    public function get($path)
    {
        return $this->request('GET', $path);
    }

    /** GET a page, then POST to $action with the page's CSRF token */
    public function submit($pagePath, $fields, $files = [], $action = null)
    {
        $page = $this->get($pagePath);
        preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $page['body'], $m);
        $fields['csrf_token'] = $m[1] ?? '';
        return $this->request('POST', $action ?: $pagePath, $fields, $files);
    }

    /** Follow a redirect and return the page */
    public function follow()
    {
        return $this->last['location'] ? $this->get($this->last['location']) : $this->last;
    }
}

function make_panorama($path, $width = 1024, $height = 512, $hue = 0)
{
    $img = imagecreatetruecolor($width, $height);
    for ($x = 0; $x < $width; $x += 8) {
        $c = imagecolorallocate($img, ((int) ($x * 255 / $width) + $hue) % 256, 120, 200 - (int) ($x * 150 / $width));
        imagefilledrectangle($img, $x, 0, $x + 7, $height, $c);
    }
    imagejpeg($img, $path, 85);
    imagedestroy($img);
}

$pano1 = "{$tmpDir}/pano1.jpg";
$pano2 = "{$tmpDir}/pano2.jpg";
make_panorama($pano1);
make_panorama($pano2, 1024, 512, 100);

// JPEG with PHP appended, uploaded under a .php name
$polyglot = "{$tmpDir}/shell.php";
file_put_contents($polyglot, file_get_contents($pano1) . '<?php echo "PWNED"; ?>');

$mailLog = BASE_PATH . '/storage/logs/mail.log';
@unlink($mailLog);
$suffix = substr(md5(uniqid()), 0, 6);
$emailA = "owner-{$suffix}@example.com";
$emailB = "other-{$suffix}@example.com";

// ---------------------------------------------------------------
echo "Public pages\n";
$anon = new Client('anon');
check($anon->get('/')['status'] === 200, 'homepage loads');
check(strpos($anon->get('/health')['body'], '"ok"') !== false, 'health check ok');
check($anon->get('/dashboard')['status'] === 302, 'dashboard requires login');
check($anon->get('/does-not-exist')['status'] === 404, 'unknown route is 404');

// ---------------------------------------------------------------
echo "Registration & login\n";
$a = new Client('tenantA');
$r = $a->submit('/?route=auth/register', [
    'company_name' => 'Acme <b>Realty</b>', 'first_name' => 'Ann', 'last_name' => 'Owner',
    'email' => $emailA, 'password' => 'short', 'password_confirm' => 'short', 'plan_id' => 1,
]);
check(strpos($r['body'], 'at least 8 characters') !== false, 'short password rejected');
check(strpos($r['body'], 'value="short"') === false, 'password not echoed back into form');

$r = $a->submit('/?route=auth/register', [
    'company_name' => 'Acme <b>Realty</b>', 'first_name' => 'Ann', 'last_name' => 'Owner',
    'email' => $emailA, 'password' => 'secret123', 'password_confirm' => 'secret123', 'plan_id' => 1,
]);
check($r['status'] === 302 && strpos($r['location'], 'dashboard') !== false, 'registration redirects to dashboard');
$dash = $a->follow();
check($dash['status'] === 200 && strpos($dash['body'], 'Acme Realty') !== false, 'dashboard shows company (tags stripped)');
check(strpos($dash['body'], 'trial ends in 14 days') !== false, 'trial banner shows 14 days');

$b = new Client('tenantB');
$b->submit('/?route=auth/register', [
    'company_name' => 'Beta Homes', 'first_name' => 'Bob', 'last_name' => 'Other',
    'email' => $emailB, 'password' => 'secret123', 'password_confirm' => 'secret123', 'plan_id' => 2,
]);
check(strpos($b->follow()['body'], 'Beta Homes') !== false, 'second tenant registered');

// ---------------------------------------------------------------
echo "Properties\n";
$r = $a->submit('/?route=property/create', [
    'title' => 'Sea View & "Garden" <script>alert(1)</script>', 'type' => 'villa', 'status' => 'for_sale',
    'price' => '1250000', 'currency' => 'AED', 'city' => 'Dubai', 'area' => 'Marina', 'bedrooms' => '4',
]);
check($r['status'] === 302, 'property created');
preg_match('/id=(\d+)/', $r['location'], $m);
$propertyId = (int) $m[1];
$page = $a->follow()['body'];
check(strpos($page, 'Sea View &amp; &quot;Garden&quot;') !== false, 'title escaped exactly once');
check(strpos($page, '&amp;amp;') === false && strpos($page, '<script>alert') === false, 'no double-escaping, no script');

$r = $a->submit('/?route=property/create', ['title' => 'Bad', 'type' => 'castle', 'status' => 'nope']);
$bad = (new Property())->first(['title' => 'Bad']);
check($bad && $bad['type'] === 'other' && $bad['status'] === 'for_sale', 'invalid enums fall back to defaults');

// ---------------------------------------------------------------
echo "Tours & scenes\n";
$r = $a->submit('/?route=tour/create&property_id=' . $propertyId, [
    'property_id' => $propertyId, 'title' => 'Sea View Villa Tour', 'description' => 'Walkthrough',
    'is_public' => 1, 'lead_form_enabled' => 1,
]);
check($r['status'] === 302 && strpos($r['location'], 'scene/create') !== false, 'tour created, redirected to add scene');
preg_match('/tour_id=(\d+)/', $r['location'], $m);
$tourId = (int) $m[1];
$tour = (new Tour())->find($tourId);
check($tour['status'] === 'draft', 'new tour is a draft');

$r = $a->submit('/?route=tour/edit&id=' . $tourId, [
    'property_id' => $propertyId, 'title' => 'Sea View Villa Tour', 'status' => 'published', 'is_public' => 1,
]);
check(strpos($r['body'], 'Add at least one scene before publishing') !== false, 'cannot publish without scenes');

$r = $a->submit("/?route=scene/create&tour_id={$tourId}", ['name' => 'Living Room'],
    ['image' => ['path' => $pano1, 'type' => 'image/jpeg', 'name' => 'living.jpg']]);
check($r['status'] === 302 && strpos($r['location'], 'scene/edit') !== false, 'scene 1 uploaded');
preg_match('/id=(\d+)/', $r['location'], $m);
$scene1 = (int) $m[1];

$r = $a->submit("/?route=scene/create&tour_id={$tourId}", ['name' => 'Kitchen'],
    ['image' => ['path' => $pano2, 'type' => 'image/jpeg', 'name' => 'kitchen.jpg']]);
preg_match('/id=(\d+)/', (string) $r['location'], $m);
$scene2 = (int) ($m[1] ?? 0);
check($scene2 > 0, 'scene 2 uploaded');

$r = $a->submit("/?route=scene/create&tour_id={$tourId}", ['name' => 'Evil'],
    ['image' => ['path' => $polyglot, 'type' => 'image/jpeg', 'name' => 'shell.php']]);
$phpFiles = glob(BASE_PATH . '/public/uploads/scenes/*.php');
check(empty($phpFiles), 'uploaded .php polyglot is never stored with a .php extension');

$s1 = (new Scene())->find($scene1);
check($s1['thumbnail_path'] && is_file(BASE_PATH . '/public' . $s1['thumbnail_path']), 'thumbnail generated');
check(preg_match('#^/uploads/scenes/[a-f0-9]{32}\.jpg$#', $s1['image_path']) === 1, 'random filename with MIME-derived extension');

// Hotspots
$r = $a->submit("/?route=scene/edit&id={$scene1}", [
    'scene_id' => $scene1, 'type' => 'navigation', 'target_scene_id' => $scene2, 'yaw' => 45, 'pitch' => -5, 'label' => 'To kitchen',
], [], '/?route=hotspot/create');
check(strpos($a->follow()['body'], 'Hotspot created successfully') !== false, 'navigation hotspot created');

$a->submit("/?route=scene/edit&id={$scene1}", [
    'scene_id' => $scene1, 'type' => 'link', 'external_url' => 'javascript:alert(1)', 'yaw' => 0, 'pitch' => 0, 'label' => 'XSS',
], [], '/?route=hotspot/create');
check(strpos($a->follow()['body'], 'must start with http') !== false, 'javascript: URL rejected');

$a->submit("/?route=scene/edit&id={$scene1}", [
    'scene_id' => $scene1, 'type' => 'info', 'yaw' => 200, 'pitch' => 10, 'label' => 'Tom\'s "view" & <b>more</b>', 'description' => '</script><script>alert(1)</script>Marble floors',
], [], '/?route=hotspot/create');
$hs = (new Hotspot())->first(['scene_id' => $scene1, 'type' => 'info']);
check($hs && (float) $hs['yaw'] === -160.0, 'yaw normalized to -180..180');

// Set initial view via AJAX endpoint
$page = $a->get("/?route=scene/edit&id={$scene1}");
preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $page['body'], $m);
$r = $a->request('POST', '/?route=scene/set-view', ['csrf_token' => $m[1], 'id' => $scene1, 'initial_yaw' => 30, 'initial_pitch' => 5]);
check(strpos($r['body'], '"success":true') !== false, 'initial view saved via AJAX');

// Reorder
$a->submit("/?route=scene&tour_id={$tourId}", ['id' => $scene2, 'direction' => 'up'], [], '/?route=scene/move');
$ordered = (new Scene())->getByTour($tourId, $tour['tenant_id']);
check((int) $ordered[0]['id'] === $scene2, 'scene moved up');

// Publish
$r = $a->submit('/?route=tour/edit&id=' . $tourId, [
    'property_id' => $propertyId, 'title' => 'Sea View Villa Tour', 'status' => 'published', 'is_public' => 1, 'lead_form_enabled' => 1,
]);
check($r['status'] === 302, 'tour published');
$tour = (new Tour())->find($tourId);

// ---------------------------------------------------------------
echo "Public viewer, embed, leads\n";
$viewer = $anon->get('/tour/view/' . $tour['slug']);
check($viewer['status'] === 200, 'public viewer (pretty URL) loads');
check(strpos($viewer['body'], trim(js_encode("Tom's \"view\" & more"), '"')) !== false, 'hotspot label stored raw, JSON-encoded with hex escapes');
check(strpos($viewer['body'], '<script>alert') === false, 'no injected script in viewer');
check(($viewer['headers']['x-frame-options'] ?? '') === 'SAMEORIGIN', 'viewer not frameable by other sites');
$embed = $anon->get('/tour/embed/' . $tour['slug']);
check($embed['status'] === 200 && !isset($embed['headers']['x-frame-options']), 'embed route is frameable');
check((int) (new Tour())->find($tourId)['views_count'] === 2, 'views counted');
check((new TourView())->count(['tour_id' => $tourId]) === 2, 'analytics rows recorded');

$r = $anon->request('POST', '/?route=lead/submit', ['tour' => $tour['slug'], 'name' => 'Visitor Vic', 'email' => 'vic@example.com', 'phone' => '+971 50 123', 'message' => '=HYPERLINK("x")']);
check(strpos($r['body'], '"success":true') !== false, 'lead submitted from public tour');
$r = $anon->request('POST', '/?route=lead/submit', ['tour' => $tour['slug'], 'name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'spam']);
check((new Lead())->count(['name' => 'Bot']) === 0, 'honeypot drops bot lead');
$r = $anon->request('POST', '/?route=lead/submit', ['tour' => $tour['slug'], 'name' => '', 'email' => 'bad']);
check($r['status'] === 422, 'invalid lead rejected');

foreach (['dashboard', 'property', 'tour', 'lead', 'team', 'settings/company', 'settings/profile', 'subscription', 'subscription/billing', "scene&tour_id={$tourId}", "scene/edit&id={$scene1}", "property/view&id={$propertyId}"] as $route) {
    $page = $a->get('/?route=' . $route);
    check($page['status'] === 200 && !preg_match('/(Warning|Notice|Deprecated|Fatal error)/', $page['body']), "tenant page {$route} renders cleanly");
}
check($anon->get('/agency/acme-realty')['status'] === 200, 'public agency page lists tours');

$leads = $a->get('/?route=lead')['body'];
check(strpos($leads, 'Visitor Vic') !== false, 'lead listed for tenant');
$csv = $a->get('/?route=lead/export')['body'];
check(strpos($csv, "'=HYPERLINK") !== false, 'CSV export neutralizes formulas');
check(strpos(@file_get_contents($mailLog), 'New lead: Visitor Vic') !== false, 'lead notification emailed');

$tourView = $a->get('/?route=tour/view&id=' . $tourId)['body'];
check(strpos($tourView, 'Unique visitors') !== false && strpos($tourView, 'tour/embed/') !== false, 'tour analytics + embed code shown');

// ---------------------------------------------------------------
echo "Tenant isolation\n";
$r = $b->get('/?route=tour/view&id=' . $tourId);
check($r['status'] === 302 && strpos($b->follow()['body'], 'Tour not found') !== false, "tenant B cannot open tenant A's tour");
$b->submit('/?route=property', ['id' => $propertyId], [], '/?route=property/delete');
check((new Property())->find($propertyId) !== false, "tenant B cannot delete tenant A's property");

// ---------------------------------------------------------------
echo "Team\n";
$r = $a->submit('/?route=team/create', ['first_name' => 'Aga', 'last_name' => 'Agent', 'email' => "agent-{$suffix}@example.com", 'role' => 'agent']);
check($r['status'] === 302, 'agent invited');
preg_match('/reset-password&token=([a-f0-9]+)/', (string) @file_get_contents($mailLog), $m);
check(!empty($m[1]), 'invitation email contains set-password link');
$agent = new Client('agent');
$agent->submit('/?route=auth/reset-password&token=' . $m[1], ['token' => $m[1], 'password' => 'agentpass1', 'password_confirm' => 'agentpass1']);
$r = $agent->submit('/?route=auth/login', ['email' => "agent-{$suffix}@example.com", 'password' => 'agentpass1']);
check($r['status'] === 302 && strpos($r['location'], 'dashboard') !== false, 'agent can log in after accepting invite');
$r = $agent->get('/?route=subscription');
check($r['status'] === 302 && strpos($r['location'], 'dashboard') !== false, 'agent cannot access billing');
check($agent->get('/?route=lead')['status'] === 200, 'agent can access leads');

// ---------------------------------------------------------------
echo "Billing\n";
$r = $a->submit('/?route=subscription', ['plan_id' => 2, 'billing_cycle' => 'yearly'], [], '/?route=subscription/change-plan');
check($r['status'] === 302 && strpos($r['location'], 'billing') !== false, 'plan changed');
$billing = $a->follow()['body'];
check(strpos($billing, 'Professional plan (yearly)') !== false && strpos($billing, 'paid') !== false, 'paid invoice created');
$sub = (new TenantSubscription())->getActiveSubscription($tour['tenant_id']);
check($sub && $sub['status'] === 'active' && (int) $sub['plan_id'] === 2 && $sub['billing_cycle'] === 'yearly', 'subscription row (not plan row) updated');
$invoice = (new Invoice())->first(['tenant_id' => $tour['tenant_id']]);
check($a->get('/?route=subscription/invoice&id=' . $invoice['id'])['status'] === 200, 'invoice printable');
check($b->get('/?route=subscription/invoice&id=' . $invoice['id'])['status'] === 302, "tenant B cannot view tenant A's invoice");

$a->submit('/?route=subscription', [], [], '/?route=subscription/cancel');
$sub = (new TenantSubscription())->getActiveSubscription($tour['tenant_id']);
check($sub && $sub['cancel_at_period_end'] == 1, 'cancel keeps access until period end');
$a->submit('/?route=subscription', [], [], '/?route=subscription/resume');
$sub = (new TenantSubscription())->getActiveSubscription($tour['tenant_id']);
check($sub && $sub['cancel_at_period_end'] == 0, 'cancellation can be undone');

// ---------------------------------------------------------------
echo "Auth hardening\n";
$r = $anon->submit('/?route=auth/forgot-password', ['email' => $emailA]);
$page = $anon->follow()['body'];
check(strpos($page, 'reset-password&amp;token') === false && strpos($page, 'If that email is registered') !== false, 'reset link not disclosed on screen');
$x = new Client('attacker');
for ($i = 0; $i < 5; $i++) {
    $x->submit('/?route=auth/login', ['email' => $emailB, 'password' => 'wrong']);
}
$r = $x->submit('/?route=auth/login', ['email' => $emailB, 'password' => 'secret123']);
check(strpos($r['body'], 'Too many failed attempts') !== false, 'login throttled after 5 failures');
(new LoginAttempt())->clear($emailB);

// ---------------------------------------------------------------
echo "Platform admin\n";
$admin = new Client('admin');
$r = $admin->submit('/?route=auth/login', ['email' => 'admin@splash360tour.com', 'password' => 'admin123']);
check($r['status'] === 302 && strpos($r['location'], 'admin/dashboard') !== false, 'admin logs in with documented password');
check($admin->follow()['status'] === 200, 'admin dashboard loads');
$tenantId = (int) $tour['tenant_id'];
$tv = $admin->get('/?route=admin/tenant-view&id=' . $tenantId)['body'];
check(strpos($tv, 'name="tenant_id" value="' . $tenantId . '"') !== false, 'tenant view posts the tenant id (not subscription id)');
foreach (['admin/invoices', 'admin/tenants', 'admin/plans', 'admin/plan-create', 'admin/plan-edit&id=1'] as $route) {
    $page = $admin->get('/?route=' . $route);
    check($page['status'] === 200 && !preg_match('/(Warning|Notice|Deprecated|Fatal error)/', $page['body']), "admin page {$route} renders cleanly");
}
$r = $admin->submit('/?route=admin/plan-create', ['name' => 'Starter', 'price_monthly' => 9, 'price_yearly' => 90,
    'max_properties' => 1, 'max_active_tours' => 1, 'max_scenes' => 5, 'max_users' => 1, 'features' => "One\nTwo"]);
check((new Plan())->findBySlug('starter-1') !== false, 'duplicate plan name gets a unique slug');
$admin->submit('/?route=admin/tenant-view&id=' . $tenantId, ['tenant_id' => $tenantId, 'status' => 'suspended'], [], '/?route=admin/tenant-status');
check((new Tenant())->find($tenantId)['status'] === 'suspended', 'tenant suspended');
check($anon->get('/tour/view/' . $tour['slug'])['status'] === 404, "suspended tenant's tour goes offline");
$r = $a->get('/?route=dashboard');
check($r['status'] === 302 && strpos($r['location'], 'auth/login') !== false, 'suspended tenant is logged out');
$admin->submit('/?route=admin/tenant-view&id=' . $tenantId, ['tenant_id' => $tenantId, 'status' => 'active'], [], '/?route=admin/tenant-status');

// ---------------------------------------------------------------
echo "Subscription lifecycle\n";
$tenantB = (new Tenant())->first(['email' => $emailB]);
$subB = (new TenantSubscription())->getActiveSubscription($tenantB['id']);
(new TenantSubscription())->update($subB['id'], ['trial_end' => date('Y-m-d H:i:s', time() - 60), 'current_period_end' => date('Y-m-d H:i:s', time() - 60)]);
$r = $b->get('/?route=dashboard');
check(strpos($r['body'], 'subscription is inactive') !== false, 'expired trial detected on next request');
$r = $b->get('/?route=property/create');
check($r['status'] === 302, 'expired tenant cannot create content');
$out = shell_exec('php ' . escapeshellarg(BASE_PATH . '/bin/cron.php') . ' 2>&1');
check(strpos((string) $out, '"renewed"') !== false, 'cron runs: ' . trim((string) $out));

// ---------------------------------------------------------------
echo "\n{$passes} passed, {$failures} failed\n";
array_map('unlink', glob("{$tmpDir}/*"));
@rmdir($tmpDir);
exit($failures ? 1 : 0);
