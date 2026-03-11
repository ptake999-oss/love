FROM php:8.2-apache

# Disable conflicting Apache modules
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Install PHP MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your website
COPY assets/_public_html/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html