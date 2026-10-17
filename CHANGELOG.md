# Changelog

## 2.0.0 — Code review fixes & Phase 2

### Critical fixes
- **Subscription row corruption**: `getActiveSubscription()` selected `ts.*, p.*`, so `$subscription['id']`
  was the *plan* id. Plan changes and cancellations updated the wrong `tenant_subscriptions` row.
- **Admin acted on the wrong tenant**: `Tenant::getWithSubscription()` selected `t.*, ts.*`, overwriting the
  tenant's `id` and `status` with the subscription's. The admin status form posted the subscription id.
- **Account takeover via password reset**: the reset link was printed on screen for any email address. Links are
  now emailed only, tokens are stored hashed and expire, and the response no longer reveals whether an email exists.
- **Remote code execution via uploads**: the stored extension came from the client file name, so a JPEG/PHP polyglot
  named `shell.php` was saved as executable PHP. Extensions now derive from the sniffed MIME type, files must decode as
  images, names are random, and `public/uploads/.htaccess` disables script execution.
- **Public tour URL always 404**: the router passed the slug as an argument but `viewer()` read `$_GET['slug']`.
- **`.env` was never loaded**: configuration silently fell back to defaults.
- **Seeded admin password**: the hash in `database.sql` was for `password`, not the documented `admin123`.

### Security
- Session fixation (no ID rotation on login), missing HttpOnly/SameSite cookie flags, no idle timeout.
- No brute-force protection on login → per-email/per-IP throttling.
- Suspended tenants could still log in and their tours stayed public.
- Deactivated users kept working sessions (session user is now refreshed from the DB each request).
- `javascript:` URLs accepted for link hotspots (opened via `window.open`).
- `json_encode()` output embedded in `<script>` without `JSON_HEX_*` flags.
- Navigation hotspots could target any scene id, including another tenant's.
- Passwords were stored in the session as "old input" after failed registration.
- Deletes used GET links (CSRF token in URL / logs); logout was a GET (CSRF logout). All are POST now.
- Flash messages rendered raw HTML; now escaped.
- `ORDER BY` identifiers interpolated without whitelisting.
- Database errors were echoed to visitors; generic error page in production, `display_errors` off.
- Added `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.

### Bugs
- `sanitize()` HTML-encoded input before storage while views escaped again → double-encoded text (`&amp;amp;`).
  Data is now stored raw (tags stripped) and escaped on output.
- Canceling a subscription removed access immediately (message promised access until period end), and the
  dashboard/subscription pages crashed on a `null` subscription. Now cancel-at-period-end, resumable.
- Trials never expired; expired/canceled subscriptions are now processed per request and by `bin/cron.php`.
- Published-tour quota only checked on create; editing a draft to published bypassed it. Users quota never enforced.
- Plan change created no invoice/payment and allowed downgrades below current usage.
- Broken redirects `?route=scene?tour_id=…` and link `?route=tour?property_id=…`.
- Tour slugs were unique per tenant but public URLs have no tenant part → cross-tenant collisions.
- Changing a tour title silently changed its public URL (now opt-in).
- Enum fields (property type/status, tour status, billing cycle, tenant status, hotspot type) were not validated,
  causing SQL errors in strict mode.
- Registration was not transactional and accepted inactive/non-existent plan ids.
- Invoice numbers used `rand(1000, 9999)` (collisions on the unique key).
- Upload failures on property images were silently ignored; replaced/deleted images left orphan files.
- `.alert-error` had no CSS; the mobile sidebar was hidden with no way to open it.
- Docker: build failed (`.env.example` excluded by `.dockerignore` but copied in the Dockerfile), GD lacked JPEG
  support, and PHP's 2 MB upload limit rejected typical panoramas.
- 360° viewer: flat image crop instead of a spherical projection, hotspot yaw not wrapped at ±180°, passive touch
  listeners prevented `preventDefault()`, crash on tours with no scenes, double slashes in image URLs.

### Phase 2 features
- WebGL 360° viewer with true spherical projection, pinch zoom, inertia, keyboard, fullscreen, auto-rotate, DOM hotspots
  and a 2D-canvas fallback.
- Visual scene editor: click to place/move hotspots, edit hotspots, save the starting view from the camera.
- Scene thumbnails (GD) and scene reordering.
- Branded public viewer (logo, accent color, contact details), scene deep links, owner preview of drafts.
- Embeddable viewer `/tour/embed/{slug}`, share buttons, public agency page `/agency/{slug}`.
- Lead capture inside tours, lead pipeline with notes, CSV export (formula-injection safe), email notifications.
- Signed webhooks (`lead.created`, `tour.published`) for n8n / CRM / Zapier with SSRF protection.
- Tour analytics: daily views, unique visitors, embed views, top referrers; dashboard KPIs.
- Team management with invitations, admin/agent roles and `max_users` enforcement.
- Company settings & personal profile (password change).
- Billing service: invoices + payments on plan change, monthly/yearly switch, printable invoices, renewals via cron,
  past-due handling, pluggable `PaymentGateway`, admin invoice list with "mark paid", admin trial extension.
- `bin/migrate.php` + `database/migrations/` for upgrades, `bin/cron.php`, `/health` endpoint, `verify.php` CLI checks.
- `tests/smoke_test.php`: 68 end-to-end checks.
