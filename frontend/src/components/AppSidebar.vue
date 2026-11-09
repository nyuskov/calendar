<template>
  <aside class="sidebar">
    <div class="sidebar__top">
      <div class="sidebar__logo">CRM</div>

      <button class="icon-btn" type="button" aria-label="Уведомления">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path
            d="M6 9a6 6 0 1 1 12 0c0 3 .8 4.5 1.6 5.5.4.5 0 1.5-.8 1.5H5.2c-.8 0-1.2-1-.8-1.5C5.2 13.5 6 12 6 9Z"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linejoin="round"
          />
          <path d="M10 19a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
      </button>
    </div>

    <nav class="nav">
      <button class="nav__item" type="button">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
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
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <rect x="3" y="4.5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.4" />
          <path d="M3 8h14M7 3v3M13 3v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
        </svg>
        Смены
      </button>
    </nav>

    <div class="sidebar__bottom">
      <button class="nav__item" type="button">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
          <path
            d="M6.39872 1.19655L6.39872 16.8034M17.3235 3.7977L17.3235 14.2023C17.3235 15.9262 15.9261 17.3237 14.2022 17.3237H3.79758C2.07369 17.3237 0.676197 15.9262 0.676197 14.2023V3.7977C0.676197 2.07381 2.07368 0.676325 3.79757 0.676325L14.2022 0.676324C15.9261 0.676324 17.3235 2.07381 17.3235 3.7977Z"
            stroke="currentColor"
            stroke-width="1.353"
          />
        </svg>
        Свернуть меню
      </button>
    </div>
  </aside>
</template>
