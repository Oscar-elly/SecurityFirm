# Use official PHP image with Apache
FROM php:8.2-apache

# Copy project files to web root
COPY public/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
