import type { ViewMode } from "~/types";

export const MONTHS_GENITIVE = [
  "января",
  "февраля",
  "марта",
  "апреля",
  "мая",
  "июня",
  "июля",
  "августа",
  "сентября",
  "октября",
  "ноября",
  "декабря",
];

export const WEEKDAYS_SHORT = ["пн", "вт", "ср", "чт", "пт", "сб", "вс"];

export function toYmd(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

export function fromYmd(value: string): Date {
  const [year = 0, month = 1, day = 1] = value.split("-").map(Number);

  return new Date(year, month - 1, day);
}

export function addDays(date: Date, days: number): Date {
  const result = new Date(date);
  result.setDate(result.getDate() + days);

  return result;
}

export function addMonths(date: Date, months: number): Date {
  const result = new Date(date);
  result.setDate(1);
  result.setMonth(result.getMonth() + months);

  return result;
}

export function startOfWeek(date: Date): Date {
  const result = new Date(date);
  const offset = (result.getDay() + 6) % 7;
  result.setDate(result.getDate() - offset);
  result.setHours(0, 0, 0, 0);

  return result;
}

export function weekdayIndex(date: Date): number {
  return (date.getDay() + 6) % 7;
}

export function isWeekend(date: Date): boolean {
  return weekdayIndex(date) >= 5;
}

export function buildDates(view: ViewMode, anchor: string): string[] {
  const base = fromYmd(anchor);

  if (view === "week") {
    const start = startOfWeek(base);

    return Array.from({ length: 7 }, (_, index) =>
      toYmd(addDays(start, index)),
    );
  }

  const start = new Date(base.getFullYear(), base.getMonth(), 1);
  const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);
  const total = end.getDate();

  return Array.from({ length: total }, (_, index) =>
    toYmd(addDays(start, index)),
  );
}

export function formatRange(from: string, to: string): string {
  const start = fromYmd(from);
  const end = fromYmd(to);
  const startMonth = MONTHS_GENITIVE[start.getMonth()] ?? "";
  const endMonth = MONTHS_GENITIVE[end.getMonth()] ?? "";

  if (
    start.getMonth() === end.getMonth() &&
    start.getFullYear() === end.getFullYear()
  ) {
    return `${start.getDate()} - ${end.getDate()} ${endMonth}`;
  }

  return `${start.getDate()} ${startMonth} - ${end.getDate()} ${endMonth}`;
}

export function dayLabel(date: string): string {
  const parsed = fromYmd(date);

  return `${WEEKDAYS_SHORT[weekdayIndex(parsed)] ?? ""} ${parsed.getDate()}`;
}

export function weekdayShort(date: string): string {
  return WEEKDAYS_SHORT[weekdayIndex(fromYmd(date))] ?? "";
}

export function dayNumber(date: string): number {
  return fromYmd(date).getDate();
}

export function shortTime(time: string): string {
  const [hours = "00", minutes = "00"] = time.split(":");

  return minutes && minutes !== "00" ? `${hours}:${minutes}` : hours;
}

export function hoursLabel(hours: number): string {
  const rounded = Math.round(hours * 10) / 10;

  return `${Number.isInteger(rounded) ? rounded : rounded.toFixed(1)} ч`;
}

export function fullTime(time: string): string {
  return time.slice(0, 5);
}

export function normalizeTime(value: string): string {
  const digits = value.replace(/\D/g, "").slice(0, 4);

  if (!digits) {
    return "";
  }

  if (digits.length <= 2) {
    return digits.padStart(2, "0");
  }

  return `${digits.slice(0, 2)}:${digits.slice(2)}`;
}

export function isValidTime(value: string): boolean {
  return /^([01]\d|2[0-3]):[0-5]\d$/.test(value);
}
