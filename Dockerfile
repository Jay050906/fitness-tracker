# Use official PHP 8.2 Apache base image
FROM php:8.2-apache

# Install required PHP extensions for MySQL connection
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html/

# Clean up script files from web root
RUN rm -f /var/www/html/docker-entrypoint.sh

# Set file permissions for Apache web server
RUN chown -R www-data:www-data /var/www/html

# Copy dynamic port entrypoint script to executable path
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose default HTTP port
EXPOSE 80

# Set entrypoint and default command
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
