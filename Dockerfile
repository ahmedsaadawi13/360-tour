FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (GD with JPEG/WEBP for scene thumbnails)
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Upload limits large enough for 360° panoramas
RUN { \
        echo 'upload_max_filesize=25M'; \
        echo 'post_max_size=26M'; \
        echo 'memory_limit=512M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/splash360.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite expires

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Set Apache document root to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set permissions
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage \
    && chmod -R 775 /var/www/html/public/uploads /var/www/html/storage

# Configuration comes from environment variables (see docker-compose.yml);
# .env / .env.example are excluded from the image by .dockerignore

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
