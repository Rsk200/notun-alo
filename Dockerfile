FROM php:8.2-apache

# Install required PHP extensions + Python + Supervisor
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    python3 \
    python3-pip \
    python3-venv \
    supervisor && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd zip pdo pdo_mysql && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# Create Python virtual environment (avoids Debian system package conflicts)
ENV VIRTUAL_ENV=/opt/venv
RUN python3 -m venv $VIRTUAL_ENV
ENV PATH="$VIRTUAL_ENV/bin:$PATH"

# Install Python dependencies for Flask RAG + impact CLI scripts
COPY requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir -r /tmp/requirements.txt && \
    rm /tmp/requirements.txt

# Tell Flask which app to run
ENV FLASK_APP=app.py

# Set working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . /var/www/html

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

# Create Apache startup script that binds to Render's $PORT
RUN { \
    echo '#!/bin/bash'; \
    echo 'set -e'; \
    echo 'APACHE_PORT="${PORT:-8080}"'; \
    echo 'sed -i "s/Listen 80/Listen ${APACHE_PORT}/g" /etc/apache2/ports.conf'; \
    echo 'sed -i "s/:80>/:${APACHE_PORT}>/g" /etc/apache2/sites-available/000-default.conf'; \
    echo 'exec apache2-foreground'; \
} > /usr/local/bin/start-apache.sh && chmod +x /usr/local/bin/start-apache.sh

# Configure Apache to run standalone (Flask RAG removed — too memory-heavy for free tier.
# The chatbot still works via the Pollinations.ai fallback in chatbot_api.php.)
# To re-enable Flask on a paid plan, add a [program:flask] section here.
RUN { \
    echo '[supervisord]'; \
    echo 'nodaemon=true'; \
    echo 'user=root'; \
    echo ''; \
    echo '[program:apache]'; \
    echo 'command=/usr/local/bin/start-apache.sh'; \
    echo 'autostart=true'; \
    echo 'autorestart=true'; \
    echo 'stdout_logfile=/dev/stdout'; \
    echo 'stdout_logfile_maxbytes=0'; \
    echo 'stderr_logfile=/dev/stderr'; \
    echo 'stderr_logfile_maxbytes=0'; \
} > /etc/supervisor/conf.d/supervisord.conf

# Expose the port Render expects
ENV PORT 8080
EXPOSE 8080

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
