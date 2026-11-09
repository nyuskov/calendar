#!/bin/bash
set -u

# Локальный запуск без Docker: Laravel (встроенный сервер) + Vite.
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
    local code=$?
    echo ""
    echo "🛑 Останавливаем серверы..."
    [ -n "$FRONT_PID" ] && kill "$FRONT_PID" 2>/dev/null
    [ -n "$BACK_PID" ] && kill "$BACK_PID" 2>/dev/null
    wait 2>/dev/null
    echo "✅ Остановлено. База осталась в backend/database/database.sqlite."
    exit "$code"
}

# Очистка на любом выходе, сигналы просто приводят к нему.
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

fail() {
    echo "❌ $1"
    exit 1
}

port_busy() {
    ss -tln 2>/dev/null | grep -qE "[:.]$1[[:space:]]"
}

check_port() {
    local port="$1" name="$2"
    if port_busy "$port"; then
        echo "❌ Порт $port ($name) уже занят."
        echo "   Скорее всего остался прошлый запуск. Освободите порт:"
        echo "     pkill -f 'artisan serve'   # API"
        echo "     pkill -f 'vite'            # приложение"
        echo "   Или запустите на других портах: API_PORT=8001 WEB_PORT=3001 ./dev-local.sh"
        exit 1
    fi
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

check_port "$API_PORT" API
check_port "$WEB_PORT" приложение

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
cd "$PROJECT_DIR/frontend" || fail "Нет каталога frontend."

if [ -d node_modules ] && [ -f node_modules/.modules.yaml ]; then
    echo "📦 Фронтенд: зависимости уже установлены."
else
    echo "📦 Фронтенд: зависимости..."
    # Ограничиваем память и конкуренцию: на маленьких VPS/hosting pnpm
    # иначе убивается OOM-киллером (exit 137).
    NODE_OPTIONS="--max-old-space-size=256" \
        pnpm install --child-concurrency=1 --network-concurrency=2 || {
        code=$?
        if [ "$code" -eq 137 ]; then
            echo "❌ Установка убита системой: не хватило памяти."
            echo "   На хостингах с лимитом RSS ~256 МБ сборка не влезает в память."
            echo "   Запускайте локально / в Docker или деплойте готовый .output."
        else
            echo "❌ pnpm install упал (код $code)."
        fi
        exit 1
    }
fi

echo "🌐 Приложение: http://localhost:$WEB_PORT"
API_ORIGIN="http://127.0.0.1:$API_PORT" pnpm dev --port "$WEB_PORT" &
FRONT_PID=$!

echo ""
echo "Готово. Ctrl+C останавливает оба сервера."
wait
