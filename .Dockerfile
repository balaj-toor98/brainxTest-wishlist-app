# Use official PHP Apache image
FROM php:8.1-apache

RUN apt-get update && apt-get install -y git unzip zlib1g-dev libzip-dev \
    && docker-php-ext-install zip

RUN a2enmod rewrite

# Install composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy application
WORKDIR /var/www/html

# Copy the public directory to the web root
COPY public/ /var/www/html/
# Copy the rest of the code (for includes, vendor, etc.)
COPY . /app

# Set DocumentRoot to /var/www/html
RUN sed -i 's#/var/www/html#/var/www/html#g' /etc/apache2/sites-available/000-default.conf

# Install dependencies
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]
