FROM php:8.3-apache

# Extensión de PostgreSQL para PDO
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Módulos de Apache usados en .htaccess y AllowOverride para habilitarlo
RUN a2enmod headers expires \
 && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY . /var/www/html/
