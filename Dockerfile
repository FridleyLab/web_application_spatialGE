FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers


# Install system packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    supervisor \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-configure gd \
    --with-jpeg=/usr/include/ \
    --with-freetype=/usr/include/ \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    intl \
    gd \
    sockets


# Install Docker CLI (for launching sibling analysis containers via mounted socket)
RUN install -m 0755 -d /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc \
    && chmod a+r /etc/apt/keyrings/docker.asc \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update \
    && apt-get install -y docker-ce-cli \
    && rm -rf /var/lib/apt/lists/*

# Install Node (for Vite)
ARG NODE_VERSION=20
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy Laravel app (first copy composer files for caching)
# COPY ./composer.json ./composer.lock ./artisan ./

# COPY ./app/helpers.php ./app/

# COPY ./bootstrap/app.php ./bootstrap/


# Copy all required files
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN npm i

RUN npm run build

RUN php artisan storage:link

# Change www-data UID/GID to 48 to match host 'apache' user for volume permissions
RUN usermod -u 48 www-data && groupmod -g 48 www-data

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# Supervisor config for Laravel queue workers
COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# Entrypoint handles Docker socket permissions, then starts supervisord
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
