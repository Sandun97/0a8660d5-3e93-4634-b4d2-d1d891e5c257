# Use official PHP image with built-in Apache
FROM php:8.3-apache

# Install necessary PHP extensions (if needed)
RUN docker-php-ext-install pdo mbstring

# Enable Apache mod_rewrite (needed for Laravel routing)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install project dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions (optional for Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
