FROM php:8.2-apache

# Enable Apache modules used by .htaccess
RUN a2enmod rewrite headers expires deflate remoteip

# PHP extensions needed by the APIs
RUN docker-php-ext-install curl fileinfo

# Allow .htaccess overrides for routing/security rules
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Ensure upload directory exists and is writable by Apache
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
