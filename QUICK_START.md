# 🚀 Quick Start Guide - Splash360 Tour

Get your 360° virtual tour platform running in **under 5 minutes**!

## ⚡ Fastest Way: Docker (Recommended)

### Prerequisites
- Docker installed ([Get Docker](https://docs.docker.com/get-docker/))
- Docker Compose installed

### One-Command Setup

```bash
./setup.sh
```

**That's it!** 🎉

### Access Your App

- **Main Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### Default Login

- **Email**: admin@splash360tour.com
- **Password**: admin123

⚠️ **Change this password immediately!**

---

## 🔧 Alternative: Traditional Setup

### Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx with mod_rewrite

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/ahmedsaadawi13/360-tour.git
   cd 360-tour
   ```

2. **Create database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE splash360_tour"
   mysql -u root -p splash360_tour < database.sql
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   nano .env  # Update DB credentials
   ```

4. **Set permissions**
   ```bash
   chmod -R 775 public/uploads
   ```

5. **Point web server to `public/` directory**

6. **Access**: http://localhost

---

## ✅ Verify Installation

Visit: `http://localhost:8080/verify.php`

This will check:
- ✓ PHP version and extensions
- ✓ Database connection
- ✓ File permissions
- ✓ Required tables

---

## 📚 Next Steps

1. **Login** as platform admin
2. **Change password** immediately
3. **Create a test agency** (tenant)
4. **Upload a 360° image** and create your first tour
5. **Delete** `verify.php` for security

---

## 📖 Full Documentation

- **Detailed Setup**: [README.md](README.md)
- **Docker Guide**: [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md)
- **Production Deploy**: [PRODUCTION_GUIDE.md](PRODUCTION_GUIDE.md)
- **Feature Summary**: [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)

---

## 🆘 Troubleshooting

### Docker Issues

```bash
# Stop and restart
docker-compose down
docker-compose up -d

# View logs
docker-compose logs -f

# Reset everything
docker-compose down -v
./setup.sh
```

### Traditional Setup Issues

**Database connection error?**
- Check `.env` credentials
- Verify MySQL is running

**404 on all pages?**
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Check `.htaccess` exists in `public/`

**Upload errors?**
```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

---

## 🎯 Key Features

✅ Multi-tenant SaaS architecture
✅ Subscription plans with quotas
✅ 360° virtual tour builder
✅ Interactive hotspots
✅ Custom panorama viewer
✅ Property management
✅ Billing & invoicing
✅ Platform admin dashboard

---

## 💡 Pro Tips

1. Use **Docker** for fastest setup and easy deployment
2. Test with `verify.php` before going live
3. Read **PRODUCTION_GUIDE.md** before deploying
4. Keep backups of database and uploads
5. Monitor logs regularly

---

**Ready to build amazing virtual tours!** 🏡✨
