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
RUN composer config -g preferred-install dist \
 && composer config -g process-timeout 2000 \
 && composer config -g github-protocols https

ENV COMPOSER_PROCESS_TIMEOUT=2000

WORKDIR /var/www/html

# Copy only composer files (better caching)
COPY composer.json composer.lock ./

# GitHub token for rate limits
ARG GITHUB_TOKEN
RUN if [ -n "$GITHUB_TOKEN" ]; then \
      composer config -g github-oauth.github.com "$GITHUB_TOKEN"; \
    fi

# Optional internal Nexus proxy
RUN composer config -g repos.packagist composer false \
 && composer config -g repositories.nexus composer \
    http://192.168.1.10:30003/repository/php-proxy

#RUN curl -I https://api.github.com/repos/php-http/discovery/zipball/82fe4c73ef3363caed49ff8dd1539ba06044910d

# Composer install with cache
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        -vvv \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --no-progress

# Copy full Magento source
COPY . .

################################
# Stage 2: Runtime (Apache)
################################
FROM php:8.2-apache

# Runtime deps only (NO git)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libicu-dev libxslt1-dev libxml2-dev \
    libonig-dev libevent-dev libssl-dev zlib1g-dev \
    && docker-php-ext-install \
        pdo_mysql mbstring gd zip intl bcmath soap xsl sockets ftp \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy app from builder
COPY --from=builder /var/www/html /var/www/html

# Magento permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 var pub/static pub/media generated

EXPOSE 80
CMD ["apache2-foreground"]
