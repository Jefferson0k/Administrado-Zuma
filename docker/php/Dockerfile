FROM php:8.3-fpm-alpine

WORKDIR /var/www

# Instalar nginx y dependencias
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libzip \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    icu \
    bash \
    nodejs \
    npm && \
    apk add --no-cache --virtual build-deps \
    icu-dev \
    zlib-dev \
    g++ \
    make \
    automake \
    autoconf \
    libzip-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd mysqli pdo_mysql intl bcmath opcache exif zip && \
    apk del build-deps && rm -rf /usr/src/php*

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar TODO el código de la aplicación
COPY . /var/www

# Copiar .env al contenedor (se crea en GitHub Actions)
COPY .env /var/www/.env

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de Node y compilar assets
RUN npm install && npm run build

# Configurar Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Crear directorios y dar permisos
RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copiar crontab
COPY docker/php/laravel.cron /etc/crontabs/root

# Configurar supervisor para correr nginx, php-fpm y cron
RUN echo -e "[supervisord]\n\
nodaemon=true\n\
\n\
[program:php-fpm]\n\
command=php-fpm\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:nginx]\n\
command=nginx -g 'daemon off;'\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:cron]\n\
command=crond -f -l 2\n\
autostart=true\n\
autorestart=true" > /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]