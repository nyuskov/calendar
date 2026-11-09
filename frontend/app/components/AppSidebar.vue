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
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <path d="M12.5 4 7 10l5.5 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
        </svg>
        Свернуть меню
      </button>
    </div>
  </aside>
</template>
