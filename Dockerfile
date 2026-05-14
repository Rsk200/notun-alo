FROM php:8.2-apache

# Install required PHP extensions (if any)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libzip-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd zip pdo pdo_mysql && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . /var/www/html

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the port Cloud Run expects (the $PORT env var, default 8080)
ENV PORT 8080
EXPOSE 8080

# Use the default Apache command
CMD ["apache2-foreground"]
