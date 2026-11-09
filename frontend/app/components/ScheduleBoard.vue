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

const selectionText = computed(
  () =>
    `Выделено: ${selectedStats.value.cells} ячеек, ` +
    `${selectedStats.value.employees} сотрудника(-ов) · ${selectedStats.value.days} дн.`,
);

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
      <div class="toolbar">
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

      <div v-if="selected.length" class="selection">
        <span class="selection__text">{{ selectionText }}</span>
        <span class="selection__spacer" />
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="openApplyMenu"
        >
          Применить шаблон
        </button>
        <button
          v-if="editableShift"
          class="selection__action"
          type="button"
          @click="focusCustomTimes"
        >
          Изменить
        </button>
        <button
          class="selection__action"
          type="button"
          :disabled="saving"
          @click="schedule.removeSelectedShifts()"
        >
          Очистить
        </button>
        <button
          class="selection__action"
          type="button"
          @click="schedule.clearSelection()"
        >
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
