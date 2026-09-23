# PHP 8.2 with Apache
FROM php:8.2-apache

# Install CA Certificates and PDO MySQL extension
RUN apt-get update && apt-get install -y ca-certificates && update-ca-certificates
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Expose port (Render / Railway dynamically injects PORT)
EXPOSE 80

# Configure Apache port dynamically
CMD ["sh", "-c", "if [ ! -z \"$PORT\" ]; then sed -i 's/80/'\"$PORT\"'/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf; fi && apache2-foreground"]
