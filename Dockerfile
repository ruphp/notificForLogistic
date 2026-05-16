FROM composer:2 AS vendor

WORKDIR /app
RUN apk add --no-cache linux-headers && docker-php-ext-install sockets
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-progress

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    icu-dev \
    linux-headers \
    libpq-dev \
    sqlite-dev \
    nginx \
    supervisor \
    $PHPIZE_DEPS \
    && docker-php-ext-install intl pdo pdo_pgsql pdo_sqlite sockets \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap

EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
