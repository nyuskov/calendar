<script setup lang="ts">
import type { MenuItem } from "~/types";

const props = defineProps<{
  items: MenuItem[];
  x: number;
  y: number;
  caption?: string;
}>();

const emit = defineEmits<{
  select: [value: string | number];
  close: [];
}>();

const root = ref<HTMLElement | null>(null);
const position = ref({ left: props.x, top: props.y });

onMounted(() => {
  const width = 210;
  const height = props.items.length * 36 + 24;
  position.value = {
    left: Math.max(8, Math.min(props.x, window.innerWidth - width - 8)),
    top: Math.max(8, Math.min(props.y, window.innerHeight - height - 8)),
  };

  const onDocumentDown = (event: MouseEvent) => {
    if (!root.value?.contains(event.target as Node)) {
      emit("close");
    }
  };

  // Откладываем на следующий тик, чтобы клик, открывший меню, его не закрыл.
  const timer = setTimeout(
    () => document.addEventListener("mousedown", onDocumentDown),
    0,
  );

  onBeforeUnmount(() => {
    clearTimeout(timer);
    document.removeEventListener("mousedown", onDocumentDown);
  });
});

function select(value: string | number): void {
  emit("select", value);
  emit("close");
}
</script>

<template>
  <div
    ref="root"
    class="ctx"
    :style="{ left: `${position.left}px`, top: `${position.top}px` }"
  >
    <div v-if="caption" class="ctx__caption">{{ caption }}</div>
    <button
      v-for="item in items"
      :key="item.value"
      type="button"
      :class="{ 'is-danger': item.danger }"
      @click="select(item.value)"
    >
      {{ item.label }}
    </button>
  </div>
</template>
