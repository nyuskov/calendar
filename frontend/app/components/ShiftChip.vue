<script setup lang="ts">
import type { Shift } from "~/types";
import { hoursLabel, shortTime } from "~/utils/date";

const props = defineProps<{
  shift: Shift;
  compact?: boolean;
}>();

const timeLabel = computed(
  () =>
    `${shortTime(props.shift.start_time)} - ${shortTime(props.shift.end_time)}`,
);

const durationLabel = computed(() => hoursLabel(props.shift.duration_hours));
</script>

<template>
  <div
    class="chip"
    :class="[`chip--${shift.color_key}`, { 'chip--compact': compact }]"
  >
    <span v-if="!compact" class="chip__time">{{ timeLabel }}</span>
    <span class="chip__hours">{{ durationLabel }}</span>
  </div>
</template>
