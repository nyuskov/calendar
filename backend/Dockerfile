FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        postgresql-dev \
        sqlite-dev \
        oniguruma-dev \
        icu-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_sqlite mbstring bcmath intl opcache \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS

# pcov активен, но собирает данные только во время тестов (PHPUnit сам вызывает pcov\start).
RUN printf 'pcov.enabled=1\npcov.directory=/app\n' > /usr/local/etc/php/conf.d/pcov.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
