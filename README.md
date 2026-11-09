# График смен — тестовое задание

Календарь смен сотрудников: недельный и месячный вид, поиск по ФИО,
назначение смен шаблонами и вручную, индикатор покрытия.

## Стек

| Слой | Технологии |
|------|------------|
| Фронтенд | Nuxt 4 (Vue 3, TypeScript), Inter, без UI-библиотек |
| Бэкенд | Laravel 13 (PHP 8.4), REST API, валидация в контроллерах |
| БД | PostgreSQL 16 |
| Запуск | Docker Compose: `db` + `backend` + `frontend` |

Все три сервиса поднимаются одной командой, код монтируется в контейнеры —
правки подхватываются без пересборки.

## Запуск

```bash
./deploy.sh
```

Скрипт соберёт образы, дождётся готовности API и Nuxt, покажет адреса и
прицепится к логам. `Ctrl+C` останавливает контейнеры (данные БД сохраняются).

Вручную:

```bash
docker compose up -d --build
```

| Что | Адрес |
|-----|-------|
| Приложение | http://localhost:3000 |
| API | http://localhost:8000/api |
| PostgreSQL | `localhost:5433`, БД/пользователь/пароль — `calendar` |

Миграции и сиды выполняются автоматически при старте контейнера `backend`.

## Проверки

```bash
# 9 feature-тестов API (SQLite in-memory)
docker compose exec backend php artisan test

# строгая проверка типов и production-сборка фронтенда
docker compose exec frontend pnpm typecheck
docker compose exec frontend pnpm build
```

## API

| Метод | Путь | Назначение |
|-------|------|------------|
| GET | `/api/employees?search=` | Список сотрудников, поиск по ФИО |
| GET | `/api/shift-templates` | Шаблоны смен (Утро, День, Вечер) |
| GET | `/api/shifts?from=&to=` | Смены за период |
| POST | `/api/shifts` | Назначить смену: `{employee_id, date, template_id}` или `{employee_id, date, start_time, end_time}` |
| POST | `/api/shifts/bulk` | Массово: `{cells: [{employee_id, date}], template_id}` либо `{employee_ids, dates, template_id}`; для своей смены — `start_time`/`end_time` |
| PATCH | `/api/shifts/{id}` | Изменить время или шаблон |
| DELETE | `/api/shifts/{id}` | Удалить смену |
| GET | `/api/coverage?from=&to=` | Количество смен по датам |

Ответы отдаются в конверте `{ "data": ... }`, ошибки валидации — `422`.

## Что реализовано

- **Календарь**: неделя (пн–вс) и месяц (все дни месяца, горизонтальная прокрутка),
  навигация по периодам, «Сегодня», липкая колонка сотрудников и шапка.
- **Поиск по ФИО** — на сервере (`ILIKE` по `last_name + first_name + middle_name`).
- **Назначение смен**:
  - клик по дате в шапке — меню быстрого выбора шаблона (на выделенные ячейки,
    а если ничего не выделено — на всех сотрудников этой даты);
  - выделение ячеек (клик или протягивание мышью) и выбор шаблона в правой панели;
  - drag-and-drop шаблона из панели прямо в ячейку;
  - «Своя смена»: произвольное начало/конец на все выделенные ячейки;
  - редактирование и удаление существующей смены по клику на неё.
- **Покрытие**: строка под таблицей пересчитывается сразу после мутаций,
  без перезагрузки страницы; 0 — красный, ≥1 — голубой.
- **Несколько смен в одной ячейке** поддерживаются (например, 08–18 и 15–18).

## Структура

```
calendar/
├── deploy.sh                 # подъём стека + ожидание готовности + логи
├── docker-compose.yml
├── backend/                  # Laravel 13
│   ├── app/Models/           # Employee, ShiftTemplate, Shift
│   ├── app/Http/Controllers/Api/
│   ├── database/migrations/
│   └── database/seeders/
└── frontend/                 # Nuxt 4
    └── app/
        ├── pages/index.vue
        ├── components/       # ScheduleBoard, ScheduleTable, SidePanel, ShiftChip, AppMenu
        ├── composables/      # useSchedule (состояние и запросы к API)
        └── utils/date.ts
```

## Модель данных

- `employees` — ФИО, роль, недельная норма часов, цвет аватара.
- `shift_templates` — название, время начала/конца, цвет, порядок.
- `shifts` — сотрудник, дата, время начала/конца, ссылка на шаблон (NULL для своей
  смены), цвет. Уникальный индекс `(employee_id, work_date, start_time)`.

Длительность смены считается на бэкенде с поддержкой перехода через полночь.
