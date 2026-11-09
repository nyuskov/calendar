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

function focusCustomTimes(): void {
  document.querySelector<HTMLInputElement>("#custom-start")?.focus();
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
        </div>
      </div>

      <div v-else class="selection">
        <span class="selection__text">
          Выделено:
          <span class="selection__count">{{ selectedStats.cells }} ячеек</span>
          {{ selectedStats.employees }} сотрудника(-ов) ·
          {{ selectedStats.days }} дн.
        </span>
        <span class="selection__spacer" />
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="openApplyMenu"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <path
              d="M7.99935 2.99993L7.99935 12.9999M2.66602 7.99993H12.9993M2.66602 4.6666L2.66602 11.3333C2.66602 12.4378 3.56145 13.3333 4.66602 13.3333H11.3327C12.4373 13.3333 13.3327 12.4378 13.3327 11.3333V4.6666C13.3327 3.56203 12.4373 2.6666 11.3327 2.6666L4.66602 2.6666C3.56145 2.6666 2.66602 3.56203 2.66602 4.6666Z"
              stroke="currentColor"
              stroke-width="0.867"
            />
          </svg>
          Применить шаблон
        </button>
        <button
          v-if="editableShift"
          class="selection__action"
          type="button"
          @click="focusCustomTimes"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <path
              d="M11.2 2.3a1.1 1.1 0 0 1 1.6 0l.9.9a1.1 1.1 0 0 1 0 1.6L5.6 12.9 3 13.5l.6-2.6 7.6-8.6Z"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linejoin="round"
            />
          </svg>
          Изменить
        </button>
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="schedule.removeSelectedShifts()"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <path
              d="M3.33203 4.54902H12.8135M6.88759 10.8235V7.05883M9.25796 10.8235V7.05883M10.4431 13.3333H5.7024C5.04784 13.3333 4.51722 12.7715 4.51722 12.0784V5.17648C4.51722 4.82994 4.78253 4.54902 5.10981 4.54902H11.0357C11.363 4.54902 11.6283 4.82994 11.6283 5.17648V12.0784C11.6283 12.7715 11.0977 13.3333 10.4431 13.3333ZM6.88759 4.54902H9.25796C9.58524 4.54902 9.85055 4.26811 9.85055 3.92157V3.29412C9.85055 2.94759 9.58524 2.66667 9.25796 2.66667H6.88759C6.56031 2.66667 6.29499 2.94759 6.29499 3.29412V3.92157C6.29499 4.26811 6.56031 4.54902 6.88759 4.54902Z"
              stroke="currentColor"
              stroke-width="0.9"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          Очистить
        </button>
        <button
          class="selection__action"
          type="button"
          @click="schedule.clearSelection()"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <g transform="translate(3.5 3.5)">
              <path
                d="M8.49403 0.505506L4.49988 4.49966M4.49988 4.49966L0.505729 8.49381M4.49988 4.49966L8.49403 8.4938M4.49988 4.49966L0.505729 0.505506"
                stroke="currentColor"
                stroke-width="1.011"
                stroke-linecap="round"
              />
            </g>
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
