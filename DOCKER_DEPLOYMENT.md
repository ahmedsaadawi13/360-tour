# Splash360 Tour - Docker Deployment Guide

## Quick Start with Docker

The easiest way to get Splash360 Tour running is using Docker. No need to manually configure PHP, MySQL, or Apache!

### Prerequisites

- Docker (20.10+)
- Docker Compose (1.29+)

### One-Command Setup

```bash
./setup.sh
```

That's it! The script will:
1. Create environment configuration
2. Set up upload directories
3. Start all services (PHP, MySQL, phpMyAdmin)
4. Import database schema

### Manual Docker Setup

If you prefer manual setup:

```bash
# 1. Create .env file
cp .env.example .env

# 2. Create upload directories
mkdir -p public/uploads/properties public/uploads/scenes
chmod -R 775 public/uploads

# 3. Start containers
docker-compose up -d

# 4. Wait for services to be ready (about 10 seconds)
```

### Access the Application

- **Main Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **MySQL Port**: 3307 (if you need direct access)

### Default Credentials

**Platform Admin:**
- Email: `admin@splash360tour.com`
- Password: `admin123`

**phpMyAdmin:**
- Server: `db`
- Username: `splash360`
- Password: `splash360_password`

**MySQL Root:**
- Username: `root`
- Password: `root_password`

⚠️ **IMPORTANT**: Change all default passwords in production!

## Docker Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f db
```

### Stop Services
```bash
docker-compose down
```

### Restart Services
```bash
docker-compose restart
```

### Rebuild After Code Changes
```bash
docker-compose down
docker-compose up -d --build
```

### Access Container Shell
```bash
# Web container
docker exec -it splash360_web bash

# Database container
docker exec -it splash360_db bash
```

### Reset Database
```bash
docker-compose down
docker volume rm 360tour_db_data
docker-compose up -d
```

## Environment Variables

Edit `.env` file to configure:

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=splash360_tour
DB_USER=splash360
DB_PASS=splash360_password
APP_URL=http://localhost:8080
```

## Production Deployment

### Using Docker in Production

1. **Update environment variables** in `docker-compose.yml`:
   - Change all passwords
   - Set production database credentials
   - Update `APP_URL` to your domain

2. **Use production database**:
   ```yaml
   db:
     environment:
       - MYSQL_ROOT_PASSWORD=STRONG_PASSWORD_HERE
       - MYSQL_PASSWORD=STRONG_PASSWORD_HERE
   ```

3. **Add SSL/HTTPS**:
   - Use a reverse proxy (nginx, Traefik, Caddy)
   - Or mount SSL certificates in the container

4. **Persistent volumes**:
   - Volumes are already configured for database and uploads
   - Back up `db_data` volume regularly

5. **Resource limits**:
   ```yaml
   web:
     deploy:
       resources:
         limits:
           cpus: '1'
           memory: 1G
   ```

### Example Production docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    build: .
    restart: always
    ports:
      - "80:80"
    environment:
      - DB_HOST=db
      - DB_NAME=splash360_tour
      - DB_USER=splash360
      - DB_PASS=${DB_PASSWORD}
      - APP_URL=https://yourdomain.com
    volumes:
      - ./public/uploads:/var/www/html/public/uploads
    depends_on:
      - db

  db:
    image: mysql:8.0
    restart: always
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=splash360_tour
      - MYSQL_USER=splash360
      - MYSQL_PASSWORD=${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

volumes:
  db_data:
```

Then use `.env` file for secrets:
```env
MYSQL_ROOT_PASSWORD=your_secure_root_password
DB_PASSWORD=your_secure_db_password
```

## Troubleshooting

### Port Already in Use

If port 8080 is already in use, edit `docker-compose.yml`:
```yaml
web:
  ports:
    - "8090:80"  # Change 8080 to any available port
```

### Database Connection Error

1. Check if database is ready:
   ```bash
   docker-compose logs db
   ```

2. Wait a few seconds and restart web container:
   ```bash
   docker-compose restart web
   ```

### Permission Issues with Uploads

```bash
docker exec -it splash360_web bash
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 775 /var/www/html/public/uploads
```

### Clear All Data and Start Fresh

```bash
docker-compose down -v
rm -rf public/uploads/*
./setup.sh
```

## Performance Optimization

### PHP Configuration

Create `php.ini` and mount it:

```ini
upload_max_filesize = 20M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 300
```

In `docker-compose.yml`:
```yaml
web:
  volumes:
    - ./php.ini:/usr/local/etc/php/conf.d/custom.ini
```

### MySQL Optimization

Add to `docker-compose.yml`:
```yaml
db:
  command: >
    --default-authentication-plugin=mysql_native_password
    --max_connections=200
    --innodb_buffer_pool_size=1G
    --query_cache_size=0
```

## Backup & Restore

### Backup Database
```bash
docker exec splash360_db mysqldump -u splash360 -psplash360_password splash360_tour > backup.sql
```

### Restore Database
```bash
docker exec -i splash360_db mysql -u splash360 -psplash360_password splash360_tour < backup.sql
```

### Backup Uploads
```bash
tar -czf uploads_backup.tar.gz public/uploads/
```

## Monitoring

### Health Checks

Add to `docker-compose.yml`:
```yaml
web:
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost/"]
    interval: 30s
    timeout: 10s
    retries: 3
```

### Resource Usage
```bash
docker stats
```

---

**Need help?** Check the main README.md or open an issue on GitHub.
