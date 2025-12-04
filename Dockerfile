# -------------------------
# Stage 1: build dependencies / install vendor via Composer
# -------------------------
FROM php:8.2-cli AS builder

# Install system dependencies and PHP extensions required to build & run Magento
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip curl openssh-client \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libzip-dev libicu-dev libxslt1-dev libxml2-dev \
        libonig-dev libevent-dev libssl-dev zlib1g-dev \
    && docker-php-ext-install \
        pdo_mysql mbstring gd zip intl bcmath soap xsl sockets ftp \
    && rm -rf /var/lib/apt/lists/*

    # optionally, create .ssh and add known_hosts
RUN mkdir -p /root/.ssh && ssh-keyscan github.com >> /root/.ssh/known_hosts

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Copy only composer metadata first — helps caching dependencies layer
COPY composer.json composer.lock /app/

# Use composer to install dependencies (no dev dependencies)
# RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# use cache mount for composer cache
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copy rest of code (Magento source, modules, etc.)
COPY . /app

# Optionally — if your build requires Magento commands (like di compile / static content), you can run them here
# e.g. RUN php bin/magento setup:di:compile && php bin/magento setup:static-content:deploy -f

# -------------------------
# Stage 2: final image with Apache + PHP
# -------------------------
FROM php:8.2-apache

# Install required PHP extensions (runtime)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git openssh-client \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libzip-dev libicu-dev libxslt1-dev libxml2-dev \
        libonig-dev libevent-dev libssl-dev zlib1g-dev \
    && docker-php-ext-install \
        pdo_mysql mbstring gd zip intl bcmath soap xsl sockets ftp \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module (needed for Magento)
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy built application + vendor from builder
COPY --from=builder /app /var/www/html

# (Optional) set proper permissions for web server user
RUN chown -R www-data:www-data /var/www/html \
    && find var pub/static pub/media generated -type d -exec chmod 775 {} + \
    && find var pub/static pub/media generated -type f -exec chmod 664 {} +

EXPOSE 80

CMD ["apache2-foreground"]