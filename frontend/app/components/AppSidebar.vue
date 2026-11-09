<template>
  <aside class="sidebar">
    <div class="sidebar__logo">CRM</div>

    <div class="user">
      <div class="avatar">ME</div>
      <div class="user__info">
        <div class="user__name">Мелихова Ева-София</div>
        <div class="user__role">администратор</div>
      </div>
      <div class="user__counter">1 + 12</div>
    </div>

    <nav class="nav">
      <button class="nav__item" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="M3 8.5 10 3l7 5.5V16a1 1 0 0 1-1 1h-4v-5H8v5H4a1 1 0 0 1-1-1V8.5Z"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linejoin="round"
          />
        </svg>
        Главная
      </button>

      <button class="nav__item is-active" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <rect
            x="3"
            y="4.5"
            width="14"
            height="12"
            rx="2"
            stroke="currentColor"
            stroke-width="1.4"
          />
          <path
            d="M3 8h14M7 3v3M13 3v3"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
          />
        </svg>
        Смены
        <span class="nav__badge">1</span>
      </button>
    </nav>

    <div class="nav__caption">админка</div>

    <nav class="nav">
      <button class="nav__item" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <circle
            cx="10"
            cy="10"
            r="2.5"
            stroke="currentColor"
            stroke-width="1.4"
          />
          <path
            d="M10 3.2v1.6M10 15.2v1.6M3.2 10h1.6M15.2 10h1.6M5.2 5.2l1.1 1.1M13.7 13.7l1.1 1.1M14.8 5.2l-1.1 1.1M6.3 13.7l-1.1 1.1"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
          />
        </svg>
        Настройки
      </button>

      <button class="nav__item" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="M5 4h10M5 8h10M5 12h7M5 16h4"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
          />
        </svg>
        Логирование
      </button>
    </nav>

    <div class="nav__caption">Все сервисы</div>

    <nav class="nav">
      <button class="nav__item" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <rect
            x="3"
            y="3"
            width="6"
            height="6"
            rx="1.5"
            stroke="currentColor"
            stroke-width="1.4"
          />
          <rect
            x="11"
            y="3"
            width="6"
            height="6"
            rx="1.5"
            stroke="currentColor"
            stroke-width="1.4"
          />
          <rect
            x="3"
            y="11"
            width="6"
            height="6"
            rx="1.5"
            stroke="currentColor"
            stroke-width="1.4"
          />
          <rect
            x="11"
            y="11"
            width="6"
            height="6"
            rx="1.5"
            stroke="currentColor"
            stroke-width="1.4"
          />
        </svg>
        Яндекс ПЭЙ проект
      </button>
    </nav>

    <div class="sidebar__bottom">
      <button class="nav__item" type="button">
        <svg
          width="20"
          height="20"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="M12.5 4 7 10l5.5 6"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
          />
        </svg>
        Свернуть меню
      </button>
    </div>
  </aside>
</template>
