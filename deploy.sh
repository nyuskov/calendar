#!/bin/bash
set -u

# Все пути считаем от каталога скрипта, чтобы запускать из любого места.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR" || exit 1

API_URL="http://localhost:8000"
APP_URL="http://localhost:3000"

cleanup() {
    echo ""
    echo "🛑 Перехвачен сигнал остановки (Ctrl+C)..."
    echo "🗑️ Останавливаем контейнеры проекта..."
    # Без -v: volume с данными Postgres сохраняем между запусками.
    docker compose down
    echo "✅ Контейнеры остановлены. База осталась в volume calendar_pgdata."
    exit 0
}

trap cleanup SIGINT SIGTERM

if ! command -v docker >/dev/null 2>&1; then
    echo "❌ Docker не найден. Установите Docker Engine и повторите."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "❌ Не найден Docker Compose v2 (команда 'docker compose')."
    exit 1
fi

echo "🚀 Поднимаем стек: Postgres + Laravel + Nuxt..."

# Если остались контейнеры от прошлого запуска (в т.ч. из другой папки),
# docker compose может упасть на конфликте имён — убираем остатки.
docker rm -f calendar-db-1 calendar-backend-1 calendar-frontend-1 >/dev/null 2>&1 || true

docker compose up -d --build --force-recreate --remove-orphans

wait_for() {
    local name="$1"
    local url="$2"
    local attempts="$3"

    echo "⏳ Ждём $name ($url)..."

    for ((i = 1; i <= attempts; i++)); do
        if curl -fsS "$url" >/dev/null 2>&1; then
            echo "✅ $name готов: $url"
            return 0
        fi

        if [ "$i" -eq "$attempts" ]; then
            echo "⚠️ $name не ответил за ${attempts} с, смотрите логи ниже."
            return 1
        fi

        sleep 1
    done
}

if command -v curl >/dev/null 2>&1; then
    wait_for "Laravel" "$API_URL/up" 90
    wait_for "Nuxt" "$APP_URL" 180
else
    echo "⚠️ curl не найден — пропускаю проверку готовности."
fi

echo ""
echo "🌐 Приложение:  $APP_URL"
echo "🔌 API:         $API_URL/api"
echo "🐘 Postgres:    localhost:5433 (calendar / calendar)"
echo "📄 API-доки:    см. README.md"
echo ""
echo "💡 Код монтируется в контейнеры — правки применяются без пересборки."
echo "🛑 Для полной остановки нажмите Ctrl+C."
echo "--------------------------------------------------------------------------------"

docker compose logs -f backend frontend
