<script setup lang="ts">
import type { MenuItem } from "~/types";
import { shortTime } from "~/utils/date";

interface MenuState {
  items: MenuItem[];
  x: number;
  y: number;
  caption?: string;
  onSelect: (value: string | number) => void;
}

const schedule = useSchedule();
const {
  templates,
  selected,
  selectedStats,
  editableShift,
  view,
  rangeLabel,
  saving,
  loading,
  search,
} = schedule;

const { show } = useToast();
const menu = shallowRef<MenuState | null>(null);

function openApplyMenu(event: MouseEvent): void {
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();

  menu.value = {
    x: rect.left,
    y: rect.bottom + 4,
    caption: "Применить шаблон",
    items: templates.value.map((template) => ({
      label: `${template.name} · ${shortTime(template.start_time)} - ${shortTime(template.end_time)}`,
      value: template.id,
    })),
    onSelect: (value) => schedule.assignTemplate(Number(value)),
  };
}

function openDateOnlyMenu(): void {
  show("Выберите шаблон, чтобы назначить смену");
}

function focusCustomTimes(): void {
  document.querySelector<HTMLInputElement>("#custom-start")?.focus();
}

function sendForApproval(): void {
  show("График отправлен на согласование");
}
</script>

<template>
  <div class="board">
    <section class="panel panel--main">
      <div v-if="!selected.length" class="toolbar">
        <label class="search">
          <svg
            width="18"
            height="18"
            viewBox="0 0 18 18"
            fill="none"
            aria-hidden="true"
          >
            <circle
              cx="8"
              cy="8"
              r="5"
              stroke="currentColor"
              stroke-width="1.4"
            />
            <path
              d="m12 12 3 3"
              stroke="currentColor"
              stroke-width="1.4"
              stroke-linecap="round"
            />
          </svg>
          <input v-model="search" type="search" placeholder="Поиск по ФИО" />
        </label>

        <div class="segmented">
          <button
            type="button"
            :class="{ 'is-active': view === 'month' }"
            @click="schedule.setView('month')"
          >
            Месяц
          </button>
          <button
            type="button"
            :class="{ 'is-active': view === 'week' }"
            @click="schedule.setView('week')"
          >
            Неделя
          </button>
        </div>

        <span class="toolbar__spacer" />

        <div class="datenav">
          <button
            class="icon-btn"
            type="button"
            aria-label="Предыдущий период"
            @click="schedule.shiftPeriod(-1)"
          >
            <svg
              width="18"
              height="18"
              viewBox="0 0 18 18"
              fill="none"
              aria-hidden="true"
            >
              <path
                d="M11 4 6 9l5 5"
                stroke="currentColor"
                stroke-width="1.4"
                stroke-linecap="round"
              />
            </svg>
          </button>
          <span class="datenav__label">{{ rangeLabel }}</span>
          <button
            class="icon-btn"
            type="button"
            aria-label="Следующий период"
            @click="schedule.shiftPeriod(1)"
          >
            <svg
              width="18"
              height="18"
              viewBox="0 0 18 18"
              fill="none"
              aria-hidden="true"
            >
              <path
                d="m7 4 5 5-5 5"
                stroke="currentColor"
                stroke-width="1.4"
                stroke-linecap="round"
              />
            </svg>
          </button>
          <button
            class="btn btn--ghost btn--sm"
            type="button"
            @click="schedule.goToday()"
          >
            Сегодня
          </button>
        </div>

        <button
          class="btn btn--ghost btn--sm"
          type="button"
          @click="sendForApproval"
        >
          Отправить на согласование
        </button>
      </div>

      <div v-else class="selection">
        <span class="selection__text">
          Выделено:
          <span class="selection__count">{{ selectedStats.cells }} ячеек</span>
          {{ selectedStats.employees }} сотрудника(-ов) · {{ selectedStats.days }} дн.
        </span>
        <span class="selection__spacer" />
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="openApplyMenu"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M8 2 14 5 8 8 2 5l6-3Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            <path d="m2 8 6 3 6-3" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            <path d="m2 11 6 3 6-3" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
          </svg>
          Применить шаблон
        </button>
        <button
          v-if="editableShift"
          class="selection__action"
          type="button"
          @click="focusCustomTimes"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M11.2 2.3a1.1 1.1 0 0 1 1.6 0l.9.9a1.1 1.1 0 0 1 0 1.6L5.6 12.9 3 13.5l.6-2.6 7.6-8.6Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
          </svg>
          Изменить
        </button>
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="schedule.removeSelectedShifts()"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          Очистить
        </button>
        <button
          class="selection__action"
          type="button"
          @click="schedule.clearSelection()"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="m4.5 4.5 7 7M11.5 4.5l-7 7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
          </svg>
          Снять
        </button>
      </div>

      <ScheduleTable />

      <div v-if="loading" class="board__loading">Обновляем график…</div>
    </section>

    <SidePanel />

    <AppMenu
      v-if="menu"
      :items="menu.items"
      :x="menu.x"
      :y="menu.y"
      :caption="menu.caption"
      @select="menu.onSelect"
      @close="menu = null"
    />
  </div>
</template>
