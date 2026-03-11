FROM php:8.2-apache

# Enable rewrite module
RUN a2enmod rewrite

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy website files
COPY assets/_public_html/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html