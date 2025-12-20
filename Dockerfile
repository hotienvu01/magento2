# syntax=docker/dockerfile:1.6

################################
# Stage 1: Composer / build
################################
FROM php:8.2-fpm AS builder

# System deps for Magento + Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    openssh-client \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libicu-dev libxslt1-dev libxml2-dev \
    libonig-dev libevent-dev libssl-dev zlib1g-dev \
    && docker-php-ext-install \
        pdo_mysql mbstring gd zip intl bcmath soap xsl sockets ftp \
    && rm -rf /var/lib/apt/lists/*

# SSH for private repos (safe even if unused)
RUN mkdir -p /root/.ssh \
 && ssh-keyscan github.com >> /root/.ssh/known_hosts

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer global config
# Force Nexus ONLY
# RUN composer config -g repositories.nexus composer http://192.168.1.10:30003/repository/php-proxy/ \
#  && composer config -g preferred-install source \
#  && composer config -g process-timeout 2000 \
#  && composer config -g --unset github-oauth.github.com \
#  && composer config -g secure-http false

# Configure Composer for HTTP Nexus
RUN composer config -g process-timeout 2000 \
 && composer config -g secure-http false \
 && composer config -g repos.nexus composer http://192.168.1.10:30003/repository/php-proxy/ \
 && composer config -g --unset github-oauth.github.com \
 && if [ -n "$GITHUB_TOKEN" ]; then composer config -g github-oauth.github.com "$GITHUB_TOKEN"; fi


#ENvironment
ENV COMPOSER_PROCESS_TIMEOUT=2000
ENV COMPOSER_DISABLE_NETWORK=0
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PREFER_DIST=1
ENV COMPOSER_USE_INCLUDE_PATH=0
ENV COMPOSER_HTTP_TIMEOUT=600
ENV CURL_HTTP_VERSION=1.1

WORKDIR /var/www/html

# GitHub token for rate limits
ARG GITHUB_TOKEN
RUN if [ -n "$GITHUB_TOKEN" ]; then \
      composer config -g github-oauth.github.com "$GITHUB_TOKEN"; \
    fi

# Optional internal Nexus proxy
# RUN composer config -g repos.packagist composer false \
#  && composer config -g repositories.nexus composer \
#     http://192.168.1.10:30003/repository/php-proxy

# #RUN curl -I https://api.github.com/repos/php-http/discovery/zipball/82fe4c73ef3363caed49ff8dd1539ba06044910d
# RUN curl -I https://objects.githubusercontent.com || true
# RUN curl -I https://api.github.com || true
# RUN curl -I https://packagist.org || true

# #Debug in Docker build
# RUN composer config --list --global
# RUN composer config --list --show-origin

# Copy Magento skeleton needed by Composer plugins
COPY app/ app/

# Copy only composer files (better caching)
COPY composer.json composer.lock ./

# Composer install with cache
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
      --no-dev \
      --prefer-dist \
      --no-progress \
      --no-scripts \
      --no-interaction

# Copy full Magento source
COPY . .

################################
# Stage 2: Runtime (Apache)
################################
FROM php:8.2-apache

# Runtime deps only (NO git) & GD dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxslt1-dev \
    libxml2-dev \
    libonig-dev \
    libevent-dev \
    libssl-dev \
    zlib1g-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        bcmath \
        soap \
        xsl \
        sockets \
        ftp \
    && rm -rf /var/lib/apt/lists/*

# # Change Apache DocumentRoot to /pub
# RUN sed -i 's#/var/www/html#/var/www/html/pub#g' /etc/apache2/sites-available/000-default.conf

# # Allow .htaccess (Magento requires this)
# RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
#     /etc/apache2/apache2.conf

RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy app from builder
COPY --from=builder /var/www/html /var/www/html

# Ensure Apache DocumentRoot points to /pub
RUN sed -i 's#/var/www/html#/var/www/html/pub#g' /etc/apache2/sites-available/000-default.conf

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html \
 && find var pub/static pub/media generated -type d -exec chmod 775 {} \; \
 && find var pub/static pub/media generated -type f -exec chmod 664 {} \;

# Edit php.ini settings (Increase memory limit, etc.)
RUN echo "memory_limit = 4G" >> /usr/local/etc/php/conf.d/99-custom.ini

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Magento Setup Install Command (Run this after the dependencies are copied)
RUN bin/magento setup:install \
    --base-url=http://192.168.1.10:32767/ \
    --db-host=192.168.1.10:32594 \
    --db-name=kyosm \
    --db-user=kyosm.admin \
    --db-password=7jnS0PI2zC \
    --admin-firstname=Admin \
    --admin-lastname=User \
    --admin-email=hotienvu01@gmail.com \
    --admin-user=admin \
    --admin-password=kyosm2025! \
    --language=en_US \
    --currency=USD \
    --timezone=UTC \
    --use-rewrites=1 \
    --search-engine=elasticsearch8 \
    --elasticsearch-host=192.168.1.10 \
    --elasticsearch-port=30015

EXPOSE 80
CMD ["apache2-foreground"]
