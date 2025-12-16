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
 && composer config -g repos.packagist composer false \
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

# Copy only composer files (better caching)
COPY composer.json composer.lock ./

# GitHub token for rate limits
ARG GITHUB_TOKEN
RUN if [ -n "$GITHUB_TOKEN" ]; then \
      composer config -g github-oauth.github.com "$GITHUB_TOKEN"; \
    fi

# Optional internal Nexus proxy
# RUN composer config -g repos.packagist composer false \
#  && composer config -g repositories.nexus composer \
#     http://192.168.1.10:30003/repository/php-proxy

#RUN curl -I https://api.github.com/repos/php-http/discovery/zipball/82fe4c73ef3363caed49ff8dd1539ba06044910d
RUN curl -I https://objects.githubusercontent.com || true
RUN curl -I https://api.github.com || true
RUN curl -I https://packagist.org || true

#Debug in Docker build
RUN composer config --list --global
RUN composer config --list --show-origin

# Composer install with cache
RUN --mount=type=cache,target=/root/.composer/cache \
    composer config -g process-timeout 2000 \
    && composer install \
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
