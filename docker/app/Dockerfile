FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        bcmath \
        intl \
        zip \
        gd \
        mbstring \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install opcache

CMD ["php-fpm"]
