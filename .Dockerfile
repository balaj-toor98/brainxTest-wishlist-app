# Use official PHP image with Apache
FROM php:8.1-apache

# Install system dependencies & php extensions if needed
RUN apt-get update && apt-get install -y git unzip zlib1g-dev libzip-dev \
    && docker-php-ext-install zip

# Enable mod_rewrite
RUN a2enmod rewrite

# Install composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy application
WORKDIR /var/www/html
COPY . /var/www/html

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (Render maps traffic automatically)
EXPOSE 80

# Use Apache default command
CMD ["apache2-foreground"]
