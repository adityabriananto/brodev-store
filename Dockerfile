# Stage 1: Build the frontend (Vue 3 + Vite)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Build the PHP runner environment
FROM php:8.3-fpm

# Install system dependencies, including Nginx and Supervisor
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application source code
COPY . /var/www

# Copy compiled frontend assets from Stage 1
COPY --from=node-builder /app/public/build /var/www/public/build

# Copy configurations
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Install Composer dependencies (production optimization)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Configure permissions
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    mkdir -p /var/log/supervisor /var/run/supervisor /run/nginx && \
    chown -R www-data:www-data /var/www
# Set PHP upload limits
RUN echo "upload_max_filesize = 10M\npost_max_size = 10M" > /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
