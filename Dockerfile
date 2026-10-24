# Gunakan PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install ekstensi sistem yang dibutuhkan
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP untuk Laravel dan MySQL
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Aktifkan mod_rewrite Apache untuk Laravel routing
RUN a2enmod rewrite

# Ubah DocumentRoot Apache ke folder /public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy file composer dari image composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy seluruh kode proyek ke dalam container
COPY . /var/www/html

# ==========================================
# BAGIAN PENTING UNTUK MEMPERBAIKI ERROR
# ==========================================

# 1. Jalankan composer install untuk membuat folder vendor
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2. Set permission untuk folder storage dan cache (Wajib untuk Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Buat entrypoint script agar Apache membaca port dari Render secara dinamis
RUN echo '#!/bin/sh\n\
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf\n\
apache2-foreground' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# 4. Gunakan script start.sh
CMD ["/usr/local/bin/start.sh"]