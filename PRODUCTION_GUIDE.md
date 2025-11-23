# Production Deployment Guide

Complete guide for deploying Splash360 Tour to production.

## Pre-Deployment Checklist

### Security
- [ ] Change all default passwords
- [ ] Generate new secure database credentials
- [ ] Remove or protect `verify.php` file
- [ ] Set up SSL/HTTPS
- [ ] Configure firewall rules
- [ ] Disable PHP error display
- [ ] Enable PHP error logging
- [ ] Set restrictive file permissions

### Configuration
- [ ] Update `.env` with production values
- [ ] Set `APP_URL` to production domain
- [ ] Configure email SMTP settings
- [ ] Set up backup cron jobs
- [ ] Configure monitoring

### Testing
- [ ] Test all features in staging environment
- [ ] Load test with expected traffic
- [ ] Test payment gateway (if enabled)
- [ ] Verify email notifications work
- [ ] Test 360° viewer on multiple devices

## Deployment Methods

### Method 1: Docker (Recommended)

**Advantages:**
- Easy setup and portability
- Consistent environment
- Easy to scale
- Includes all dependencies

**Steps:**

1. **On your server:**
```bash
# Clone repository
git clone <your-repo-url>
cd 360Tour

# Create production .env
cp .env.example .env
nano .env  # Update with production values

# Run setup
chmod +x setup.sh
./setup.sh
```

2. **Configure domain:**
```bash
# Add reverse proxy (Nginx example)
sudo nano /etc/nginx/sites-available/splash360
```

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

3. **Enable SSL with Let's Encrypt:**
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### Method 2: Traditional LAMP Stack

**Requirements:**
- Apache 2.4+ or Nginx
- PHP 7.4+ with extensions
- MySQL 5.7+

**Steps:**

1. **Install dependencies:**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-mbstring php-gd php-xml

# CentOS/RHEL
sudo yum install httpd mysql-server php php-mysql php-mbstring php-gd php-xml
```

2. **Upload files:**
```bash
# Via Git
cd /var/www/html
git clone <your-repo-url> splash360
cd splash360

# Or via FTP/SFTP
# Upload entire project to /var/www/html/splash360
```

3. **Configure Apache:**
```bash
sudo nano /etc/apache2/sites-available/splash360.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/splash360/public

    <Directory /var/www/html/splash360/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splash360_error.log
    CustomLog ${APACHE_LOG_DIR}/splash360_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite splash360
sudo a2enmod rewrite
sudo systemctl restart apache2
```

4. **Set up database:**
```bash
mysql -u root -p
```

```sql
CREATE DATABASE splash360_tour CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'splash360'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON splash360_tour.* TO 'splash360'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
mysql -u splash360 -p splash360_tour < database.sql
```

5. **Configure environment:**
```bash
cp .env.example .env
nano .env
```

Update values:
```env
DB_HOST=localhost
DB_NAME=splash360_tour
DB_USER=splash360
DB_PASS=YOUR_STRONG_PASSWORD
APP_URL=https://yourdomain.com
```

6. **Set permissions:**
```bash
sudo chown -R www-data:www-data /var/www/html/splash360
sudo chmod -R 755 /var/www/html/splash360
sudo chmod -R 775 /var/www/html/splash360/public/uploads
```

7. **Enable SSL:**
```bash
sudo certbot --apache -d yourdomain.com
```

### Method 3: Shared Hosting (cPanel)

**Steps:**

1. **Upload files:**
   - Upload via File Manager or FTP
   - Extract to `public_html/` or subdirectory

2. **Create database:**
   - Use cPanel MySQL Database Wizard
   - Create database and user
   - Import `database.sql` via phpMyAdmin

3. **Configure .env:**
   - Copy `.env.example` to `.env`
   - Update database credentials
   - Update APP_URL

4. **Verify .htaccess:**
   - Ensure `.htaccess` is in `public/` directory
   - If mod_rewrite issues, contact hosting support

5. **Test installation:**
   - Access `yourdomain.com/verify.php`
   - Fix any reported issues

## Production Configuration

### PHP Configuration

Create or edit `php.ini`:

```ini
; Error handling
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php/error.log

; Performance
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; File uploads
upload_max_filesize = 20M
post_max_size = 25M

; Security
expose_php = Off
allow_url_fopen = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### MySQL Optimization

```sql
-- my.cnf or my.ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 0
```

### Apache Performance

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# Security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
```

## Security Hardening

### 1. File Permissions

```bash
# Files: 644
find . -type f -exec chmod 644 {} \;

# Directories: 755
find . -type d -exec chmod 755 {} \;

# Uploads: 775
chmod -R 775 public/uploads

# Protect sensitive files
chmod 600 .env
chmod 600 config/*.php
```

### 2. Database Security

```sql
-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove remote root access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Reload privileges
FLUSH PRIVILEGES;
```

### 3. Disable Directory Listing

In `.htaccess`:
```apache
Options -Indexes
```

### 4. Protect Sensitive Files

Create `.htaccess` in root:
```apache
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.md$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(database\.sql|composer\.(json|lock)|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 5. Change Default Credentials

```sql
-- Change admin password (login first)
UPDATE users SET password = '$2y$10$NEW_HASH_HERE' WHERE email = 'admin@splash360tour.com';

-- Or login and use the UI to change password
```

## Monitoring & Maintenance

### Set Up Backups

**Database backup script:**

```bash
#!/bin/bash
# /usr/local/bin/backup-splash360.sh

BACKUP_DIR="/backups/splash360"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="splash360_tour"
DB_USER="splash360"
DB_PASS="your_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/html/splash360/public/uploads

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Cron job:**
```bash
crontab -e

# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-splash360.sh >> /var/log/backup-splash360.log 2>&1
```

### Monitoring

**Health check script:**

```bash
#!/bin/bash
# Check if application is responding

URL="https://yourdomain.com"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" $URL)

if [ $RESPONSE -eq 200 ]; then
    echo "OK: Application is responding"
else
    echo "ERROR: Application returned $RESPONSE"
    # Send alert (email, Slack, etc.)
fi
```

### Log Rotation

```bash
sudo nano /etc/logrotate.d/splash360
```

```
/var/log/splash360/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

## Performance Optimization

### Enable OPcache

In `php.ini`:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### Use CDN for Static Assets

Configure CloudFlare or similar CDN:
1. Point DNS to CloudFlare
2. Enable caching for static files
3. Enable minification
4. Enable HTTP/2

### Database Indexing

Already optimized in schema, but verify:
```sql
SHOW INDEX FROM properties;
SHOW INDEX FROM tours;
SHOW INDEX FROM scenes;
```

## Troubleshooting

### Application not loading

1. Check Apache/Nginx error logs
2. Check PHP error logs
3. Verify file permissions
4. Test database connection
5. Check .htaccess is present

### 404 errors on all pages

- Enable mod_rewrite: `sudo a2enmod rewrite`
- Check AllowOverride in Apache config
- Verify .htaccess exists in public/

### Upload errors

```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

### Database connection errors

1. Verify credentials in `.env`
2. Check MySQL is running
3. Test connection: `mysql -u splash360 -p`

## Post-Deployment

1. **Test all features:**
   - Login/logout
   - Create property
   - Create tour with scenes
   - Add hotspots
   - View public tour
   - Change subscription plan

2. **Monitor performance:**
   - Check server resources
   - Monitor error logs
   - Track response times

3. **Set up analytics:**
   - Google Analytics
   - Server monitoring (New Relic, Datadog)
   - Uptime monitoring (UptimeRobot, Pingdom)

4. **Documentation:**
   - Document your specific setup
   - Keep credentials secure
   - Document any customizations

---

**Need help?** Open an issue on GitHub or contact support.
