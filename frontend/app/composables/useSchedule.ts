import type { Employee, Shift, ShiftTemplate, ViewMode } from "~/types";
import {
  addDays,
  addMonths,
  buildDates,
  formatRange,
  fromYmd,
  isValidTime,
  toYmd,
} from "~/utils/date";

const API = "/api";

export interface CellKey {
  employeeId: number;
  date: string;
}

export function cellKey(employeeId: number, date: string): string {
  return `${employeeId}:${date}`;
}

export function parseCell(key: string): CellKey | null {
  const [employeeId, date] = key.split(":");

  if (!employeeId || !date) {
    return null;
  }

  return { employeeId: Number(employeeId), date };
}

export function useSchedule() {
  const employees = useState<Employee[]>("schedule:employees", () => []);
  const templates = useState<ShiftTemplate[]>("schedule:templates", () => []);
  const shifts = useState<Shift[]>("schedule:shifts", () => []);
  const search = useState<string>("schedule:search", () => "");
  const view = useState<ViewMode>("schedule:view", () => "week");
  const anchor = useState<string>("schedule:anchor", () => toYmd(new Date()));
  const selected = useState<string[]>("schedule:selected", () => []);
  const loading = useState<boolean>("schedule:loading", () => false);
  const saving = useState<boolean>("schedule:saving", () => false);
  const ready = useState<boolean>("schedule:ready", () => false);
  const errorMessage = useState<string | null>("schedule:error", () => null);

  const dates = computed(() => buildDates(view.value, anchor.value));
  const from = computed(() => dates.value[0] ?? toYmd(new Date()));
  const to = computed(() => dates.value[dates.value.length - 1] ?? from.value);
  const rangeLabel = computed(() => formatRange(from.value, to.value));
  const isMonth = computed(() => view.value === "month");

  const shiftsByCell = computed(() => {
    const map = new Map<string, Shift[]>();

    for (const shift of shifts.value) {
      const key = cellKey(shift.employee_id, shift.date);
      const list = map.get(key);

      if (list) {
        list.push(shift);
      } else {
        map.set(key, [shift]);
      }
    }

    for (const list of map.values()) {
      list.sort((a, b) => a.start_time.localeCompare(b.start_time));
    }

    return map;
  });

  const coverage = computed(() => {
    const map = new Map<string, number>();

    for (const shift of shifts.value) {
      map.set(shift.date, (map.get(shift.date) ?? 0) + 1);
    }

    return map;
  });

  const templateUsage = computed(() => {
    const map = new Map<number, number>();

    for (const shift of shifts.value) {
      if (shift.shift_template_id === null) {
        continue;
      }

      map.set(
        shift.shift_template_id,
        (map.get(shift.shift_template_id) ?? 0) + 1,
      );
    }

    return map;
  });

  const selectedCells = computed<CellKey[]>(() =>
    selected.value
      .map(parseCell)
      .filter((cell): cell is CellKey => cell !== null),
  );

  const selectedShifts = computed(() => {
    const keys = new Set(selected.value);

    return shifts.value.filter((shift) =>
      keys.has(cellKey(shift.employee_id, shift.date)),
    );
  });

  const selectedStats = computed(() => ({
    cells: selected.value.length,
    employees: new Set(selectedCells.value.map((cell) => cell.employeeId)).size,
    days: new Set(selectedCells.value.map((cell) => cell.date)).size,
  }));

  /**
   * Смена, которую можно отредактировать: выделена ровно одна ячейка,
   * и в ней ровно одна смена.
   */
  const editableShift = computed<Shift | null>(() => {
    if (selected.value.length !== 1 || selectedShifts.value.length !== 1) {
      return null;
    }

    return selectedShifts.value[0] ?? null;
  });

  async function request<T>(
    path: string,
    options: Record<string, unknown> = {},
  ): Promise<T> {
    const response = await $fetch<{ data: T }>(`${API}${path}`, options);

    return response.data;
  }

  async function loadEmployees(): Promise<void> {
    const query = search.value.trim();
    const suffix = query ? `?search=${encodeURIComponent(query)}` : "";
    employees.value = await request<Employee[]>(`/employees${suffix}`);
  }

  async function loadTemplates(): Promise<void> {
    templates.value = await request<ShiftTemplate[]>("/shift-templates");
  }

  async function loadShifts(): Promise<void> {
    shifts.value = await request<Shift[]>(
      `/shifts?from=${from.value}&to=${to.value}`,
    );
  }

  async function loadAll(): Promise<void> {
    loading.value = true;
    errorMessage.value = null;

    // Первый запрос может попасть на ещё не прогретый dev-сервер — повторяем.
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      try {
        await Promise.all([loadEmployees(), loadTemplates(), loadShifts()]);
        ready.value = true;
        loading.value = false;
        return;
      } catch (error) {
        console.error(error);

        if (attempt === 3) {
          errorMessage.value = "Не удалось загрузить график смен";
        } else {
          await new Promise((resolve) => setTimeout(resolve, 700 * attempt));
        }
      }
    }

    loading.value = false;
  }

  async function refreshShifts(): Promise<void> {
    try {
      await loadShifts();
    } catch (error) {
      console.error(error);
      errorMessage.value = "Не удалось обновить смены";
    }
  }

  function toggleCell(employeeId: number, date: string): void {
    const key = cellKey(employeeId, date);

    if (selected.value.includes(key)) {
      selected.value = selected.value.filter((item) => item !== key);
    } else {
      selected.value = [...selected.value, key];
    }
  }

  function addCell(employeeId: number, date: string): void {
    const key = cellKey(employeeId, date);

    if (!selected.value.includes(key)) {
      selected.value = [...selected.value, key];
    }
  }

  function clearSelection(): void {
    selected.value = [];
  }

  function setView(next: ViewMode): void {
    if (view.value === next) {
      return;
    }

    view.value = next;
    clearSelection();
  }

  function shiftPeriod(direction: -1 | 1): void {
    anchor.value =
      view.value === "week"
        ? toYmd(addDays(fromYmd(anchor.value), 7 * direction))
        : toYmd(addMonths(fromYmd(anchor.value), direction));
    clearSelection();
  }

  function goToday(): void {
    anchor.value = toYmd(new Date());
    clearSelection();
  }

  function setAnchor(date: string): void {
    anchor.value = date;
    clearSelection();
  }

  async function assignTemplate(
    templateId: number,
    cells = selectedCells.value,
  ): Promise<void> {
    if (!cells.length) {
      errorMessage.value = "Выделите хотя бы одну ячейку";
      return;
    }

    saving.value = true;

    try {
      await request("/shifts/bulk", {
        method: "POST",
        body: {
          template_id: templateId,
          cells: cells.map((cell) => ({
            employee_id: cell.employeeId,
            date: cell.date,
          })),
        },
      });
      clearSelection();
      await loadShifts();
    } catch (error) {
      console.error(error);
      errorMessage.value = "Не удалось назначить смену";
    } finally {
      saving.value = false;
    }
  }

  async function assignCustom(
    startTime: string,
    endTime: string,
  ): Promise<void> {
    if (!startTime || !endTime) {
      errorMessage.value = "Укажите начало и конец смены";
      return;
    }

    if (!isValidTime(startTime) || !isValidTime(endTime)) {
      errorMessage.value = "Время должно быть в формате ЧЧ:ММ";
      return;
    }

    if (startTime === endTime) {
      errorMessage.value = "Начало и конец смены не могут совпадать";
      return;
    }

    const editing = editableShift.value;

    saving.value = true;

    try {
      if (editing) {
        await request(`/shifts/${editing.id}`, {
          method: "PATCH",
          body: { start_time: startTime, end_time: endTime },
        });
      } else {
        if (!selectedCells.value.length) {
          errorMessage.value = "Выделите ячейки в календаре";
          return;
        }

        await request("/shifts/bulk", {
          method: "POST",
          body: {
            start_time: startTime,
            end_time: endTime,
            cells: selectedCells.value.map((cell) => ({
              employee_id: cell.employeeId,
              date: cell.date,
            })),
          },
        });
      }

      clearSelection();
      await loadShifts();
    } catch (error) {
      console.error(error);
      errorMessage.value = "Не удалось сохранить смену";
    } finally {
      saving.value = false;
    }
  }

  async function removeShift(id: number): Promise<void> {
    saving.value = true;

    try {
      await request(`/shifts/${id}`, { method: "DELETE" });
      selected.value = selected.value.filter((key) => {
        const shift = shifts.value.find((item) => item.id === id);

        return !shift || key !== cellKey(shift.employee_id, shift.date);
      });
      await loadShifts();
    } catch (error) {
      console.error(error);
      errorMessage.value = "Не удалось удалить смену";
    } finally {
      saving.value = false;
    }
  }

  async function removeSelectedShifts(): Promise<void> {
    const targets = [...selectedShifts.value];

    if (!targets.length) {
      clearSelection();
      return;
    }

    saving.value = true;

    try {
      await Promise.all(
        targets.map((shift) =>
          request(`/shifts/${shift.id}`, { method: "DELETE" }),
        ),
      );
      clearSelection();
      await loadShifts();
    } catch (error) {
      console.error(error);
      errorMessage.value = "Не удалось очистить смены";
    } finally {
      saving.value = false;
    }
  }

  return {
    employees,
    templates,
    shifts,
    search,
    view,
    anchor,
    selected,
    loading,
    saving,
    ready,
    errorMessage,
    dates,
    from,
    to,
    rangeLabel,
    isMonth,
    shiftsByCell,
    coverage,
    templateUsage,
    selectedCells,
    selectedShifts,
    selectedStats,
    editableShift,
    loadAll,
    loadEmployees,
    loadTemplates,
    loadShifts,
    refreshShifts,
    toggleCell,
    addCell,
    clearSelection,
    setView,
    shiftPeriod,
    goToday,
    setAnchor,
    assignTemplate,
    assignCustom,
    removeShift,
    removeSelectedShifts,
  };
}
