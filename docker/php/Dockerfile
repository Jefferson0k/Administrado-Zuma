FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    mysql-client \
    nodejs \
    npm \
    bash \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev \
    oniguruma-dev

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Instalar Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuario
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Configurar PHP
COPY local.ini /usr/local/etc/php/conf.d/local.ini

# Cambiar propietario
RUN chown -R www:www /var/www

USER www

EXPOSE 9000

CMD ["php-fpm"]