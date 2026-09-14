FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Arguments defined in docker-compose.yml
ARG user=developer
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    ca-certificates \
    gnupg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install modern Node.js and NPM for frontend asset compilation
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using official docker-php-extension-installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        sqlite3 \
        redis \
        bcmath \
        ctype \
        curl \
        dom \
        fileinfo \
        filter \
        gd \
        intl \
        mbstring \
        opcache \
        openssl \
        pcntl \
        posix \
        session \
        tokenizer \
        xml \
        zip \
        opentelemetry \
        xdebug

# Install latest Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Create system user to run Composer and Artisan Commands matching host UID/GID
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# Set working directory permissions
RUN chown -R $user:www-data /var/www/html

USER $user

EXPOSE 9000

CMD ["php-fpm"]
