#!/bin/bash
set -u

# Локальный запуск без Docker: Laravel (встроенный сервер) + Nuxt.
# База — SQLite-файл, чтобы не поднимать PostgreSQL.
# Переменные окружения имеют приоритет над backend/.env, поэтому
# существующий .env с pgsql можно не менять.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR" || exit 1

API_PORT="${API_PORT:-8000}"
WEB_PORT="${WEB_PORT:-3000}"
DB_FILE="$PROJECT_DIR/backend/database/database.sqlite"

BACK_PID=""
FRONT_PID=""

cleanup() {
    echo ""
    echo "🛑 Останавливаем серверы..."
    [ -n "$BACK_PID" ] && kill "$BACK_PID" 2>/dev/null
    [ -n "$FRONT_PID" ] && kill "$FRONT_PID" 2>/dev/null
    wait 2>/dev/null
    echo "✅ Остановлено. База осталась в backend/database/database.sqlite."
    exit 0
}

trap cleanup SIGINT SIGTERM

fail() {
    echo "❌ $1"
    exit 1
}

# ---------- Проверки ----------

command -v php >/dev/null 2>&1 || fail "Не найден PHP. Установите php8.3+ (php-cli) и повторите."
command -v composer >/dev/null 2>&1 || fail "Не найден Composer. https://getcomposer.org/download/"
command -v node >/dev/null 2>&1 || fail "Не найден Node.js 20+."
command -v pnpm >/dev/null 2>&1 || fail "Не найден pnpm (corepack enable)."

php -m | grep -qi '^pdo_sqlite$' || fail "В PHP нет расширения pdo_sqlite (нужно для локальной базы)."

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
if ! php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    fail "Нужен PHP 8.3+, установлен $PHP_VERSION."
fi

echo "🔧 PHP $PHP_VERSION, Node $(node -v), pnpm $(pnpm -v)"

# ---------- Бэкенд ----------

echo ""
echo "📦 Бэкенд: зависимости..."
cd "$PROJECT_DIR/backend" || fail "Нет каталога backend."
composer install --no-interaction --prefer-dist >/dev/null || fail "composer install упал."

[ -f .env ] || cp .env.example .env

if ! grep -qE '^APP_KEY=base64:' .env; then
    echo "🔑 Генерируем APP_KEY..."
    php artisan key:generate --force >/dev/null || fail "Не удалось сгенерировать APP_KEY."
fi

touch "$DB_FILE"

echo "🗄️ Миграции и сиды (SQLite: database/database.sqlite)..."
DB_CONNECTION=sqlite DB_DATABASE="$DB_FILE" \
    php artisan migrate --seed --force >/dev/null || fail "Миграции упали."

echo "🚀 API: http://localhost:$API_PORT"
DB_CONNECTION=sqlite DB_DATABASE="$DB_FILE" \
    php artisan serve --host=127.0.0.1 --port="$API_PORT" &
BACK_PID=$!

# ---------- Фронтенд ----------

echo ""
echo "📦 Фронтенд: зависимости..."
cd "$PROJECT_DIR/frontend" || fail "Нет каталога frontend."
pnpm install --silent || fail "pnpm install упал."

echo "🌐 Приложение: http://localhost:$WEB_PORT"
NUXT_API_BASE="http://127.0.0.1:$API_PORT/api" pnpm dev --port "$WEB_PORT" &
FRONT_PID=$!

echo ""
echo "Готово. Ctrl+C останавливает оба сервера."
wait
