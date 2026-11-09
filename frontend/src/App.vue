<script setup lang="ts">
import { onBeforeUnmount, onMounted, watch } from "vue";
import AppSidebar from "@/components/AppSidebar.vue";
import ScheduleBoard from "@/components/ScheduleBoard.vue";
import { useSchedule } from "@/composables/useSchedule";
import { useToast } from "@/composables/useToast";

const schedule = useSchedule();
const { search, view, anchor, errorMessage, loading, ready } = schedule;
const { message, show, hide } = useToast();

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    schedule.loadEmployees().catch(() => {
      errorMessage.value = "Не удалось загрузить сотрудников";
    });
  }, 250);
});

watch([view, anchor], () => {
  schedule.loadShifts().catch(() => {
    errorMessage.value = "Не удалось загрузить смены";
  });
});

watch(errorMessage, (value) => {
  if (value) {
    show(value);
    errorMessage.value = null;
  }
});

onMounted(() => {
  schedule.loadAll();
});

onBeforeUnmount(() => {
  clearTimeout(searchTimer);
});

async function save(): Promise<void> {
  await schedule.loadShifts();
  show("Все изменения сохранены");
}

function publish(): void {
  show("График опубликован");
}
</script>

<template>
  <div class="shell">
    <AppSidebar />

    <main class="content">
      <div class="breadcrumbs">
        <button type="button" aria-label="Назад">
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
        <span>Графики смен</span>
      </div>

      <div class="content__title-row">
        <h1 class="content__title">График</h1>
        <div class="content__actions">
          <button class="btn btn--tinted" type="button" @click="publish">
            Опубликовать
          </button>
          <button class="btn btn--primary" type="button" @click="save">
            Сохранить
          </button>
        </div>
      </div>

      <ScheduleBoard />

      <div v-if="loading" class="loading-bar" />
      <div v-if="!ready && !loading" class="empty">Загружаем график…</div>
    </main>

    <div v-if="message" class="toast" @click="hide">
      {{ message }}
    </div>
  </div>
</template>
