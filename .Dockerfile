# 1. Use PHP with Apache
FROM php:8.1-apache

# 2. Install system deps & PHP extensions
RUN apt-get update && apt-get install -y git unzip zlib1g-dev libzip-dev \
    && docker-php-ext-install zip

# 3. Enable Apache mod_rewrite (for pretty URLs if needed)
RUN a2enmod rewrite

# 4. Install Composer from official image
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy all project files into container
COPY . /var/www/html

# 7. Install PHP dependencies (this creates /vendor inside container)
RUN composer install --no-dev --optimize-autoloader

# 8. Change Apache DocumentRoot to /var/www/html/public (because index.php lives there)
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# 9. Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# 10. Expose port
EXPOSE 80

# 11. Start Apache
CMD ["apache2-foreground"]
