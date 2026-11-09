<script setup lang="ts">
import { fullTime, hoursLabel, normalizeTime } from "~/utils/date";

const schedule = useSchedule();
const { templates, selected, editableShift, templateUsage, saving } = schedule;
const { show } = useToast();

const start = ref("09:00");
const end = ref("17:00");

watch(editableShift, (shift) => {
  if (shift) {
    start.value = shift.start_time;
    end.value = shift.end_time;
  }
});

const canApply = computed(() => selected.value.length > 0 && !saving.value);

function pickTemplate(templateId: number): void {
  if (!selected.value.length) {
    show("Сначала выделите ячейки в календаре");
    return;
  }

  schedule.assignTemplate(templateId);
}

function dragStart(event: DragEvent, templateId: number): void {
  if (!event.dataTransfer) {
    return;
  }

  event.dataTransfer.setData("text/x-shift-template", String(templateId));
  event.dataTransfer.effectAllowed = "copy";
}

function applyCustom(): void {
  schedule.assignCustom(start.value, end.value);
}

function normalizeStart(): void {
  start.value = normalizeTime(start.value);
}

function normalizeEnd(): void {
  end.value = normalizeTime(end.value);
}
</script>

<template>
  <aside class="panel panel--side">
    <section class="side__section">
      <h2 class="side__title">Шаблоны смен</h2>

      <button
        v-for="template in templates"
        :key="template.id"
        type="button"
        class="template-card"
        draggable="true"
        :disabled="saving"
        @dragstart="dragStart($event, template.id)"
        @click="pickTemplate(template.id)"
      >
        <span>
          <span class="template-card__name">{{ template.name }}</span>
          <span class="template-card__time">
            {{ fullTime(template.start_time) }} -
            {{ fullTime(template.end_time) }}
            <span class="muted">·</span>
            {{ hoursLabel(template.duration_hours) }}
          </span>
        </span>
        <span class="template-card__count">{{
          templateUsage.get(template.id) ?? 0
        }}</span>
      </button>
    </section>

    <section class="side__section">
      <h2 class="side__title">Своя смена</h2>

      <div class="custom-times">
        <div class="field">
          <label for="custom-start">Начало</label>
          <input
            id="custom-start"
            v-model="start"
            type="text"
            inputmode="numeric"
            maxlength="5"
            placeholder="09:00"
            @blur="normalizeStart"
          />
        </div>
        <div class="field">
          <label for="custom-end">Конец</label>
          <input
            id="custom-end"
            v-model="end"
            type="text"
            inputmode="numeric"
            maxlength="5"
            placeholder="17:00"
            @blur="normalizeEnd"
          />
        </div>
      </div>

      <button
        class="btn btn--primary"
        type="button"
        :disabled="!canApply"
        @click="applyCustom"
      >
        {{ editableShift ? "Изменить" : "Поставить в выделенные" }}
      </button>

      <p v-if="!selected.length" class="side__hint">
        Выделите ячейки в календаре, чтобы назначить смену. Шаблон можно
        перетащить прямо в ячейку.
      </p>
      <p v-else-if="editableShift" class="side__hint">
        Смена выделена: измените время и нажмите «Изменить».
      </p>
    </section>
  </aside>
</template>
