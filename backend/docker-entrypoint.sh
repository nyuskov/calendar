#!/bin/sh
set -e

# Зависимости ставятся один раз — при первом запуске контейнера.
if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

# Ключ приложения нужен для шифрования сессий и подписи cookie.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan config:clear >/dev/null 2>&1 || true

exec "$@"
