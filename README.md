# Splash360 Tour - Virtual Reality Tours for Real Estate

A complete, production-ready SaaS web application for creating and viewing 360° virtual tours for the real estate sector.

## Features

### Multi-Tenant SaaS Platform
- **Tenant Management**: Each real estate agency has its own isolated account
- **Subscription Plans**: Flexible pricing with trial periods, quotas, and billing
- **User Roles**: Platform admin, tenant admin, and agent roles
- **Usage Tracking**: Monitor properties, tours, scenes, and enforce plan limits

### 360° Virtual Tours
- **Property Management**: Full CRUD for real estate properties
- **Tour Builder**: Create immersive 360° tours with multiple scenes
- **Scene Management**: Upload panoramic images with customizable viewing angles
- **Hotspots**: Interactive navigation, information, and link hotspots
- **Public Viewer**: Beautiful, responsive 360° tour viewer

### Subscription & Billing
- **Trial System**: 14-day trial for new accounts
- **Multiple Plans**: Starter, Professional, and Enterprise tiers
- **Quota Enforcement**: Automatic limits based on subscription plan
- **Invoice Management**: Track invoices and payment history
- **Dummy Payment Gateway**: Extensible payment integration structure

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

3. **Create the database**
   ```bash
   mysql -u root -p
   ```
   ```sql
   CREATE DATABASE splash360_tour CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

4. **Import the database schema**
   ```bash
   mysql -u root -p splash360_tour < database.sql
   ```

5. **Set file permissions**
   ```bash
   chmod 755 -R public/
   chmod 775 -R public/uploads/
   chown -R www-data:www-data public/uploads/
   ```

6. **Configure web server**

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
       try_files $uri $uri/ /index.php?route=$uri&$args;
   }
   ```

7. **Access the application**

   Visit: `http://localhost` (or your configured domain)

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
│   ├── Services/           # Business logic services
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
   - Add hotspots for navigation and information

4. **Publish Tours**
   - Set tour status to "Published"
   - Share the public URL with clients

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

- **Password Hashing**: Using PHP's `password_hash()`
- **CSRF Protection**: Token-based form validation
- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: Input sanitization and output escaping
- **Multi-tenant Isolation**: Query-level tenant_id filtering
- **Session Management**: Secure session handling

## Customization

### Adding New Payment Gateways

1. Create a new payment service class in `app/Services/`
2. Implement the payment interface
3. Update the subscription controller to use the new gateway

### Customizing Email Notifications

1. Edit email templates in `app/Services/EmailService.php`
2. Configure SMTP settings in `config/app.php`

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
- Contact: support@splash360tour.com

## Future Enhancements

Potential features for future versions:
- Email notifications (password reset, invoices)
- Multi-language support
- Advanced analytics and tour statistics
- Mobile app (iOS/Android)
- Social media sharing
- Custom branding per tenant
- Advanced 360° editor with visual hotspot placement
- Video integration in tours
- Floor plan integration
- Lead capture forms
- Virtual reality (VR) headset support

## Credits

Developed as a complete, production-ready SaaS platform for real estate virtual tours.

---

**Version**: 1.0.0
**Last Updated**: 2024
