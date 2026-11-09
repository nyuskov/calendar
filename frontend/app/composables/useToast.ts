let toastTimer: ReturnType<typeof setTimeout> | undefined;

export function useToast() {
  const message = useState<string | null>("app:toast", () => null);

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
