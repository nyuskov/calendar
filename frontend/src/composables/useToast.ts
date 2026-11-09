import { ref } from "vue";

// Одно сообщение на всё приложение — состояние живёт в модуле,
// поэтому composable можно вызывать из любого компонента.
const message = ref<string | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

export function useToast() {
  function show(text: string): void {
    message.value = text;

    if (toastTimer) {
      clearTimeout(toastTimer);
    }

    toastTimer = setTimeout(() => {
      message.value = null;
    }, 3000);
  }

  function hide(): void {
    message.value = null;
  }

  return { message, show, hide };
}
