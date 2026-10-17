# Splash360 Tour - Virtual Reality Tours for Real Estate

A complete, production-ready SaaS web application for creating and viewing 360° virtual tours for the real estate sector.

## Features

### Multi-Tenant SaaS Platform
- **Tenant Management**: Each real estate agency has its own isolated account
- **Subscription Plans**: Flexible pricing with trial periods, quotas, and billing
- **User Roles**: Platform admin, tenant admin, and agent roles
- **Usage Tracking**: Monitor properties, tours, scenes, and enforce plan limits

### 360° Virtual Tours
- **Property Management**: Full CRUD with search and filters (type, status, city)
- **Tour Builder**: Draft → publish workflow, scene ordering, stable shareable links
- **WebGL 360° Viewer**: True spherical projection, drag/touch/pinch, zoom, keyboard, fullscreen, auto-rotate, 2D fallback
- **Visual Hotspot Editor**: Click on the panorama to place navigation, info and link hotspots; save the starting view from the current camera
- **Branded Public Viewer**: Tenant logo, accent color and contact details; deep links to scenes (`#scene-12`)
- **Share & Embed**: Public link, iframe embed code (`/tour/embed/{slug}`), WhatsApp/Facebook/email sharing
- **Agency Page**: `/agency/{tenant-slug}` lists all public published tours of a tenant

### Leads, Analytics & Integrations
- **Lead Capture**: "Contact agent" form inside every tour (honeypot + rate limit), email notification
- **Lead Pipeline**: Status (new → contacted → qualified → closed/lost), notes, filters, CSV export
- **Webhooks**: `lead.created` and `tour.published` events POSTed as signed JSON (n8n, vTiger/HubSpot CRM, Zapier)
- **Tour Analytics**: Daily views, unique visitors (privacy-friendly hashed), embed views, top referrers

### Team & Roles
- **Tenant Admin**: Everything, including team, branding, subscription and billing
- **Agent**: Properties, tours, scenes and leads
- **Invitations**: Admins invite members by email (set-password link); plan `max_users` is enforced

### Subscription & Billing
- **Trial System**: 14-day trial; expired trials take public tours offline and block new content
- **Multiple Plans**: Starter, Professional, Enterprise with monthly/yearly billing
- **Quota Enforcement**: Properties, published tours, scenes and users; downgrade checks against usage
- **Invoices & Payments**: Invoice per charge, printable invoices, payment history
- **Cancel at Period End**: Access continues until the paid period ends; cancellation can be undone
- **Renewals**: `bin/cron.php` renews, marks failed payments past-due and expires trials
- **Pluggable Gateway**: `PaymentGateway` interface with a dummy gateway for development

## Technology Stack

- **Backend**: PHP 7.4+ (no frameworks - simple, clean architecture)
- **Database**: MySQL 5.7+
- **Frontend**: Vanilla HTML/CSS/JavaScript
- **360 Viewer**: Custom vanilla JS implementation

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- mod_rewrite enabled (for Apache)

### Step-by-Step Setup

1. **Clone or download the project**
   ```bash
   cd /var/www/html
   # Or your web server's document root
   ```

2. **Configure database connection**

   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

   Edit `.env` with your database credentials:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=splash360_tour
   DB_USER=root
   DB_PASS=your_password

   APP_URL=http://localhost
   ```

3. **Generate an application key** (used to hash visitor fingerprints and sign webhooks)
   ```bash
   php -r "echo bin2hex(random_bytes(32));"   # put the value in APP_KEY
   ```

4. **Create the database**
   ```bash
   mysql -u root -p
   ```
   ```sql
   CREATE DATABASE splash360_tour CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

5. **Import the database schema**
   ```bash
   mysql -u root -p splash360_tour < database.sql
   ```

6. **Set file permissions**
   ```bash
   chmod 755 -R public/
   chmod 775 -R public/uploads/ storage/
   chown -R www-data:www-data public/uploads/ storage/
   ```

7. **Configure web server**

   **For Apache:**
   - Ensure `.htaccess` is in the `public/` directory
   - Ensure `mod_rewrite` is enabled:
     ```bash
     sudo a2enmod rewrite
     sudo service apache2 restart
     ```
   - Set your document root to the `public/` directory

   **For Nginx:**
   Add to your server block:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$args;
   }
   # Never execute scripts from uploads
   location ~* ^/uploads/.*\.(php|phtml|phar)$ {
       deny all;
   }
   ```

   Raise PHP upload limits for panoramas (php.ini): `upload_max_filesize = 25M`, `post_max_size = 26M`.

8. **Schedule the billing/maintenance job** (hourly)
   ```bash
   0 * * * * php /var/www/html/bin/cron.php >> /var/www/html/storage/logs/cron.log 2>&1
   ```

9. **Verify the installation**
   ```bash
   php verify.php
   ```

   For local development you can use PHP's built-in server:
   ```bash
   php -S localhost:8000 -t public public/index.php
   ```

10. **Access the application**

   Visit: `http://localhost` (or your configured domain)

## Upgrading an Existing Installation

Back up the database, pull the new code, then apply pending migrations:

```bash
php bin/migrate.php
```

Migration `001_phase2.sql` adds branding/webhook columns, leads, analytics, login throttling and
cancel-at-period-end subscriptions. Tour slugs become globally unique (public URLs have no tenant
part); if two tenants share a slug, rename one before migrating:
`SELECT slug, COUNT(*) FROM tours GROUP BY slug HAVING COUNT(*) > 1;`

Password reset tokens are now stored hashed, so links emailed before the upgrade stop working.

## Default Credentials

### Platform Admin
- **Email**: `admin@splash360tour.com`
- **Password**: `admin123`
- **IMPORTANT**: Change this password immediately after first login!

## Project Structure

```
360Tour/
├── app/
│   ├── Controllers/        # Application controllers
│   ├── Models/             # Database models
│   ├── Middleware/         # Authentication & authorization
│   ├── Helpers/            # Helper functions
│   ├── Services/           # Billing, payment gateway, mailer, webhooks, images
│   ├── Database.php        # Database connection
│   └── Router.php          # Simple routing system
├── config/
│   ├── app.php             # Application configuration
│   └── database.php        # Database configuration
├── public/
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   ├── images/             # Static images
│   ├── uploads/            # User uploaded files
│   ├── index.php           # Front controller
│   └── .htaccess           # Apache rewrite rules
├── views/
│   ├── layouts/            # Layout templates
│   ├── partials/           # Reusable view components
│   ├── auth/               # Authentication views
│   ├── tenant/             # Tenant dashboard views
│   ├── property/           # Property management views
│   ├── tour/               # Tour management views
│   ├── scene/              # Scene management views
│   ├── subscription/       # Subscription views
│   └── admin/              # Platform admin views
├── bin/                    # migrate.php, cron.php
├── database/migrations/    # Upgrade migrations for existing installs
├── storage/logs/           # mail.log, php-error.log
├── tests/                  # End-to-end smoke test
├── database.sql            # Database schema & seed data
├── .env.example            # Environment variables example
└── README.md               # This file
```

## Usage Guide

### For Tenants (Real Estate Agencies)

1. **Register an Account**
   - Visit the registration page
   - Enter company details and select a plan
   - Start with a 14-day free trial

2. **Create Properties**
   - Add property details (title, type, price, location, etc.)
   - Upload property images

3. **Create Virtual Tours**
   - Select a property
   - Create a new tour
   - Upload 360° panoramic images as scenes
   - Click on the panorama to place navigation, info and link hotspots
   - Save the starting view from the current camera position

4. **Publish & Share Tours**
   - Set tour status to "Published"
   - Share the public URL, or embed it on your website with the iframe code
   - Follow up on leads under **Leads** (or receive them in n8n/CRM via webhook)

5. **Manage Subscription**
   - View current plan and usage
   - Upgrade/downgrade plans
   - View billing history

### For Platform Admins

1. **Access Admin Dashboard**
   - Login with platform admin credentials
   - View platform-wide statistics

2. **Manage Tenants**
   - View all registered agencies
   - Activate/suspend accounts
   - Monitor usage

3. **Manage Plans**
   - Create subscription plans
   - Set quotas and pricing
   - Activate/deactivate plans

## 360° Image Requirements

- **Format**: Equirectangular projection
- **File Types**: JPG, PNG
- **Recommended Resolution**: 4096x2048 or higher
- **Aspect Ratio**: 2:1
- **Max File Size**: 10MB

### How to Create 360° Images

1. Use a 360° camera (e.g., Ricoh Theta, Insta360)
2. Or use panorama mode on smartphone
3. Or stitch multiple images using software like PTGui

## Security Features

- **Password Hashing**: `password_hash()` with transparent rehash on login, 8-character minimum
- **Brute-force Protection**: Login throttling per email and per IP
- **Session Security**: HttpOnly/SameSite cookies, strict mode, ID rotation on login, idle timeout
- **CSRF Protection**: Tokens on every state-changing form; deletes are POST-only
- **SQL Injection Prevention**: Prepared statements; ORDER BY identifiers whitelisted
- **XSS Protection**: Data stored raw and escaped on output (`e()`), `js_encode()` for inline scripts, `http(s)`-only URLs
- **Safe Uploads**: MIME sniffing + image decoding, random names, extension from MIME type, script execution disabled in `uploads/`
- **Multi-tenant Isolation**: Every lookup scoped by `tenant_id`; hotspot targets restricted to the same tour
- **Password Reset**: Hashed, expiring tokens delivered by email only; no account enumeration
- **Webhooks**: HMAC-SHA256 signature header, SSRF protection (public IPs only unless `WEBHOOK_ALLOW_PRIVATE=1`)
- **Headers**: `X-Frame-Options: SAMEORIGIN` (relaxed only for `/tour/embed/*`), `nosniff`, referrer policy

## Customization

### Adding New Payment Gateways

1. Implement `PaymentGateway` (`app/Services/PaymentGateway.php`): `charge(array $tenant, array $invoice)` and `name()`
2. Register the class in `PaymentGatewayFactory::make()`
3. Set `PAYMENT_GATEWAY=<name>` in `.env`

### Email

`MAIL_DRIVER=log` writes messages to `storage/logs/mail.log` (development). `MAIL_DRIVER=mail` uses
PHP `mail()`; configure sendmail or an SMTP relay on the server. See `app/Services/Mailer.php`.

### Webhooks (n8n / CRM)

Set a webhook URL under **Branding & Settings**. Each event is POSTed as JSON:

```json
{
  "event": "lead.created",
  "sent_at": "2026-10-17T10:00:00+00:00",
  "tenant": {"id": 3, "name": "Acme Realty", "slug": "acme-realty"},
  "data": {
    "lead_id": 42, "name": "Jane Buyer", "email": "jane@example.com", "phone": "+971...",
    "message": "...",
    "tour": {"id": 7, "title": "Marina Villa", "url": "https://.../tour/view/marina-villa"},
    "property": {"id": 5, "title": "Marina Villa", "city": "Dubai", "price": 1250000, "currency": "AED"}
  }
}
```

Verify authenticity with the `X-Splash360-Signature: sha256=<hmac>` header (HMAC-SHA256 of the raw
body using `APP_KEY`). Events: `lead.created`, `tour.published`, `webhook.test`.

### Theming

- Main styles: `public/css/style.css`
- CSS variables for easy color customization
- All colors defined in `:root` selector

## Database Schema

### Key Tables

- **tenants**: Real estate agencies
- **users**: User accounts (linked to tenants)
- **plans**: Subscription plan definitions
- **tenant_subscriptions**: Active subscriptions
- **properties**: Real estate listings
- **tours**: Virtual tour records
- **scenes**: 360° panoramic scenes
- **hotspots**: Interactive points in scenes
- **invoices**: Billing invoices
- **payments**: Payment records
- **leads**: Enquiries captured from public tours
- **tour_views**: Analytics events (hashed visitor id, referrer, embed flag)
- **login_attempts**: Failed logins for throttling
- **schema_migrations**: Applied migrations (`bin/migrate.php`)

## API Extension

While this version doesn't include a REST API, you can easily add one:

1. Create an `api/` directory in `public/`
2. Add authentication via API tokens
3. Create JSON response endpoints for mobile apps

## Troubleshooting

### Images not uploading
- Check `public/uploads/` directory permissions (775)
- Verify `upload_max_filesize` in php.ini (recommended: 10M)

### Routing not working
- Ensure mod_rewrite is enabled (Apache)
- Verify .htaccess is in `public/` directory
- Check server configuration for try_files directive (Nginx)

### Database connection errors
- Verify credentials in `.env`
- Check MySQL service is running
- Ensure database exists and is accessible

### 360° viewer not loading
- Check browser console for JavaScript errors
- Verify scene images are accessible
- Ensure images are in equirectangular format
- Panoramas larger than the GPU's max texture size are downscaled automatically; 8192×4096 is a good maximum

## Testing

`tests/smoke_test.php` runs an end-to-end scenario over HTTP (registration, uploads, hotspots,
publishing, leads, team invitations, billing, tenant isolation, admin suspension, cron) against a
**throwaway** database:

```bash
mysql -e "CREATE DATABASE splash360_test" && mysql splash360_test < database.sql
export DB_NAME=splash360_test APP_URL=http://127.0.0.1:8099 APP_ENV=development
php -S 127.0.0.1:8099 -t public public/index.php &
php tests/smoke_test.php
```

## Performance Optimization

### For Production

1. **Enable OPcache** in php.ini:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   ```

2. **Enable Gzip compression** (Apache):
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/css text/javascript
   </IfModule>
   ```

3. **Optimize images**: Use compressed JPEGs for 360° scenes

4. **Add caching headers** for static assets

5. **Consider CDN** for uploaded images

## License

This project is provided as-is for educational and commercial use.

## Support

For issues and questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Contact: ahmed.sha3ban13@gmail.com

## Roadmap

Delivered in v2.0: email notifications, tour analytics, social sharing, custom branding, visual
hotspot editor, lead capture, team management, embeds, webhooks.

Next candidates:
- Real payment gateway (Stripe / PayTabs) with stored payment methods and proration
- REST API with per-tenant tokens (feed tours/leads into portals and mobile apps)
- Floor plans with scene markers, video and image-gallery hotspots
- Multi-language viewer (Arabic RTL), custom domains per tenant
- WebXR / VR headset mode, gyroscope look-around on mobile
- Image tiling/multi-resolution for very large panoramas

## Credits

Developed as a complete, production-ready SaaS platform for real estate virtual tours.

---

**Version**: 2.0.0
**Last Updated**: 2025
