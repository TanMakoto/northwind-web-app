# PHP 8.2 with Apache
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Expose port (Railway dynamically injects PORT)
EXPOSE 80

# Configure Apache port dynamically for Railway
CMD ["sh", "-c", "sed -i 's/80/'\"$PORT\"'/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
