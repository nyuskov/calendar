<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from "vue";
import type { ContextMenu, Shift } from "@/types";
import { cellKey, useSchedule } from "@/composables/useSchedule";
import AppMenu from "@/components/AppMenu.vue";
import ShiftChip from "@/components/ShiftChip.vue";
import {
  dayLabel,
  dayNumber,
  fromYmd,
  isWeekend,
  shortTime,
  toYmd,
  weekdayShort,
} from "@/utils/date";

const schedule = useSchedule();
const {
  employees,
  templates,
  dates,
  isMonth,
  loading,
  selected,
  shiftsByCell,
  coverage,
} = schedule;

const menu = shallowRef<ContextMenu | null>(null);
const dropKey = ref<string | null>(null);
// Протягивание мышью добавляет ячейки — как в таблицах.
const dragging = ref(false);

const gridStyle = computed(() => ({
  "--days": String(dates.value.length),
  "--day-col": isMonth.value ? "64px" : "minmax(96px, 1fr)",
}));

function cellShifts(employeeId: number, date: string): Shift[] {
  return shiftsByCell.value.get(cellKey(employeeId, date)) ?? [];
}

function isSelected(employeeId: number, date: string): boolean {
  return selected.value.includes(cellKey(employeeId, date));
}

/**
 * Внешние границы выделенного блока: правую и нижнюю линии рисуем только
 * там, где справа/снизу нет выделённой ячейки. Иначе на стыке получается
 * двойная рамка 2px вместо одной линии.
 */
const selectionEdges = computed(() => {
  const map = new Map<string, { right: boolean; bottom: boolean }>();
  const set = new Set(selected.value);

  employees.value.forEach((employee, row) => {
    dates.value.forEach((date, col) => {
      const key = cellKey(employee.id, date);

      if (!set.has(key)) {
        return;
      }

      const nextEmployee = employees.value[row + 1];
      const nextDate = dates.value[col + 1];

      map.set(key, {
        right: !(nextDate && set.has(cellKey(employee.id, nextDate))),
        bottom: !(nextEmployee && set.has(cellKey(nextEmployee.id, date))),
      });
    });
  });

  return map;
});

function edgeClass(employeeId: number, date: string): Record<string, boolean> {
  const edge = selectionEdges.value.get(cellKey(employeeId, date));

  return {
    "sel--right": edge?.right ?? false,
    "sel--bottom": edge?.bottom ?? false,
  };
}

function onCellDown(employeeId: number, date: string, event: MouseEvent): void {
  if (event.button !== 0) {
    return;
  }

  menu.value = null;
  const key = cellKey(employeeId, date);

  if (selected.value.includes(key)) {
    selected.value = selected.value.filter((item) => item !== key);
    return;
  }

  dragging.value = true;
  schedule.addCell(employeeId, date);
}

function stopDragging(): void {
  dragging.value = false;
}

function onCellEnter(
  employeeId: number,
  date: string,
  event: MouseEvent,
): void {
  if (!dragging.value) {
    return;
  }

  // Кнопку могли отпустить вне таблицы — тогда протягивание заканчивается.
  if ((event.buttons & 1) === 0) {
    dragging.value = false;
    return;
  }

  schedule.addCell(employeeId, date);
}

function openShiftMenu(event: MouseEvent, shift: Shift): void {
  event.stopPropagation();
  event.preventDefault();

  menu.value = {
    x: event.clientX,
    y: event.clientY,
    items: [
      { label: "Изменить время", value: "edit" },
      { label: "Удалить", value: "delete", danger: true },
    ],
    onSelect: (value) => {
      if (value === "edit") {
        selected.value = [cellKey(shift.employee_id, shift.date)];
      } else {
        schedule.removeShift(shift.id);
      }
    },
  };
}

function openDateMenu(event: MouseEvent, date: string): void {
  event.preventDefault();

  menu.value = {
    x: event.clientX,
    y: event.clientY + 24,
    caption: `Быстрое назначение · ${dayLabel(date)}`,
    items: templates.value.map((template) => ({
      label: `${template.name} · ${shortTime(template.start_time)} - ${shortTime(template.end_time)}`,
      value: template.id,
    })),
    onSelect: (value) => {
      const templateId = Number(value);
      const cellsInDate = schedule.selectedCells.value.filter(
        (cell) => cell.date === date,
      );
      const cells = cellsInDate.length
        ? cellsInDate
        : employees.value.map((employee) => ({
            employeeId: employee.id,
            date,
          }));

      schedule.assignTemplate(templateId, cells);
    },
  };
}

function onDrop(employeeId: number, date: string, event: DragEvent): void {
  event.preventDefault();
  dropKey.value = null;

  const raw = event.dataTransfer?.getData("text/x-shift-template");

  if (!raw) {
    return;
  }

  const key = cellKey(employeeId, date);
  const cells =
    selected.value.includes(key) && selected.value.length > 1
      ? schedule.selectedCells.value
      : [{ employeeId, date }];

  schedule.assignTemplate(Number(raw), cells);
}

function closeMenu(): void {
  menu.value = null;
}

function onDragLeave(employeeId: number, date: string): void {
  if (dropKey.value === cellKey(employeeId, date)) {
    dropKey.value = null;
  }
}

const today = toYmd(new Date());

onMounted(() => {
  window.addEventListener("mouseup", stopDragging);
  window.addEventListener("pointerup", stopDragging);
  window.addEventListener("blur", stopDragging);
});

onBeforeUnmount(() => {
  window.removeEventListener("mouseup", stopDragging);
  window.removeEventListener("pointerup", stopDragging);
  window.removeEventListener("blur", stopDragging);
});
</script>

<template>
  <div class="table-scroll" @mouseleave="stopDragging">
    <div class="schedule" :style="gridStyle">
      <div class="schedule__row schedule__head">
        <div class="schedule__head-cell col-employee">Сотрудник</div>

        <button
          v-for="date in dates"
          :key="date"
          type="button"
          class="schedule__head-cell schedule__head-cell--days"
          :class="{
            'is-month-head': isMonth,
            'is-weekend': isWeekend(fromYmd(date)),
            'is-today': date === today,
          }"
          @click="openDateMenu($event, date)"
        >
          <template v-if="isMonth">
            <span class="head-weekday">{{ weekdayShort(date) }}</span>
            <span class="head-day">{{ dayNumber(date) }}</span>
          </template>
          <template v-else>{{ dayLabel(date) }}</template>
        </button>
      </div>

      <div v-if="!employees.length && !loading" class="empty">
        Сотрудники не найдены
      </div>

      <div
        v-for="(employee, index) in employees"
        :key="employee.id"
        class="schedule__row"
        :class="{ 'row--alt': index % 2 === 1 }"
      >
        <div class="employee-cell col-employee">
          <div class="avatar" :style="{ background: employee.avatar_color }">
            {{ employee.initials }}
          </div>
          <div class="employee-cell__info">
            <div class="employee-cell__name">{{ employee.full_name }}</div>
            <div class="employee-cell__meta">
              {{ employee.role }} · {{ employee.weekly_hours }} ч
            </div>
          </div>
        </div>

        <div v-for="date in dates" :key="date" class="cell">
          <div
            class="cell__inner"
            :class="{
              'is-weekend': isWeekend(fromYmd(date)),
              'is-selected': isSelected(employee.id, date),
              'is-drop-target': dropKey === cellKey(employee.id, date),
              ...edgeClass(employee.id, date),
            }"
            @mousedown.prevent="onCellDown(employee.id, date, $event)"
            @mouseenter="onCellEnter(employee.id, date, $event)"
            @dragover.prevent="dropKey = cellKey(employee.id, date)"
            @dragleave="onDragLeave(employee.id, date)"
            @drop="onDrop(employee.id, date, $event)"
          >
            <ShiftChip
              v-for="shift in cellShifts(employee.id, date)"
              :key="shift.id"
              :shift="shift"
              :compact="isMonth"
              @contextmenu.prevent.stop="openShiftMenu($event, shift)"
            />

            <span
              v-if="!cellShifts(employee.id, date).length"
              class="cell__placeholder"
            >
              <span class="plus">+</span>
            </span>
          </div>
        </div>
      </div>

      <div class="schedule__row coverage">
        <div class="coverage__label col-employee">Покрытие</div>
        <div
          v-for="date in dates"
          :key="date"
          class="coverage__cell"
          :class="
            (coverage.get(date) ?? 0) > 0
              ? 'coverage__cell--filled'
              : 'coverage__cell--empty'
          "
        >
          {{ coverage.get(date) ?? 0 }}
        </div>
      </div>
    </div>

    <AppMenu
      v-if="menu"
      :items="menu.items"
      :x="menu.x"
      :y="menu.y"
      :caption="menu.caption"
      @select="menu.onSelect"
      @close="closeMenu"
    />
  </div>
</template>
