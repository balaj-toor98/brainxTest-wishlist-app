# 1. Use PHP 8.1 with Apache
FROM php:8.1-apache

# 2. Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y git unzip zlib1g-dev libzip-dev \
    && docker-php-ext-install zip

# 3. Enable Apache mod_rewrite
RUN a2enmod rewrite

# 4. Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy all project files into the container
COPY . /var/www/html

# 7. Install PHP dependencies and rebuild autoload
RUN composer install --no-dev --optimize-autoloader && composer dump-autoload -o

# 8. Set Apache DocumentRoot to /public (where index.php lives)
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# 9. Fix permissions
RUN chown -R www-data:www-data /var/www/html

# 10. Expose HTTP port
EXPOSE 80

# 11. Start Apache
CMD ["apache2-foreground"]
