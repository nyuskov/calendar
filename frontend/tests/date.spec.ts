import { describe, expect, it } from "vitest";
import {
  addDays,
  addMonths,
  buildDates,
  dayLabel,
  dayNumber,
  formatRange,
  fromYmd,
  fullTime,
  hoursLabel,
  isWeekend,
  isValidTime,
  normalizeTime,
  shortTime,
  startOfWeek,
  toYmd,
  weekdayIndex,
  weekdayShort,
} from "../src/utils/date";

describe("toYmd / fromYmd", () => {
  it("сериализует и разбирает дату без сдвигов часового пояса", () => {
    expect(toYmd(new Date(2026, 10, 9))).toBe("2026-11-09");

    const parsed = fromYmd("2026-11-09");

    expect(parsed.getFullYear()).toBe(2026);
    expect(parsed.getMonth()).toBe(10);
    expect(parsed.getDate()).toBe(9);
  });
});

describe("startOfWeek", () => {
  it("начинает неделю с понедельника", () => {
    expect(toYmd(startOfWeek(new Date(2026, 10, 9)))).toBe("2026-11-09");
    expect(toYmd(startOfWeek(new Date(2026, 10, 15)))).toBe("2026-11-09");
  });
});

describe("сдвиг дат", () => {
  it("прибавляет дни через границу месяца", () => {
    expect(toYmd(addDays(new Date(2026, 10, 30), 2))).toBe("2026-12-02");
    expect(toYmd(addDays(new Date(2026, 10, 1), -1))).toBe("2026-10-31");
  });

  it("переключает месяц и всегда встаёт на первое число", () => {
    expect(toYmd(addMonths(new Date(2026, 10, 15), 1))).toBe("2026-12-01");
    expect(toYmd(addMonths(new Date(2026, 0, 31), -1))).toBe("2025-12-01");
  });
});

describe("выходные", () => {
  it("определяет субботу и воскресенье", () => {
    expect(weekdayIndex(new Date(2026, 10, 9))).toBe(0);
    expect(isWeekend(new Date(2026, 10, 13))).toBe(false);
    expect(isWeekend(new Date(2026, 10, 14))).toBe(true);
    expect(isWeekend(new Date(2026, 10, 15))).toBe(true);
  });
});

describe("buildDates", () => {
  it("неделя — семь дней с понедельника", () => {
    const dates = buildDates("week", "2026-11-11");

    expect(dates).toHaveLength(7);
    expect(dates[0]).toBe("2026-11-09");
    expect(dates[6]).toBe("2026-11-15");
  });

  it("месяц — все дни ноября", () => {
    const dates = buildDates("month", "2026-11-20");

    expect(dates).toHaveLength(30);
    expect(dates[0]).toBe("2026-11-01");
    expect(dates[29]).toBe("2026-11-30");
  });
});

describe("formatRange", () => {
  it("внутри одного месяца", () => {
    expect(formatRange("2026-11-09", "2026-11-15")).toBe("9 - 15 ноября");
  });

  it("через границу месяцев", () => {
    expect(formatRange("2026-11-29", "2026-12-05")).toBe(
      "29 ноября - 5 декабря",
    );
  });
});

describe("подписи дней", () => {
  it("день недели, короткий день и число", () => {
    expect(dayLabel("2026-11-09")).toBe("пн 9");
    expect(weekdayShort("2026-11-15")).toBe("вс");
    expect(dayNumber("2026-11-15")).toBe(15);
  });
});

describe("время", () => {
  it("короткий и полный формат", () => {
    expect(shortTime("07:00:00")).toBe("07");
    expect(shortTime("09:30")).toBe("09:30");
    expect(fullTime("07:00:00")).toBe("07:00");
  });

  it("часы с дробной частью", () => {
    expect(hoursLabel(8)).toBe("8 ч");
    expect(hoursLabel(7.5)).toBe("7.5 ч");
  });

  it("нормализует ручной ввод", () => {
    expect(normalizeTime("930")).toBe("09:30");
    expect(normalizeTime("09:30")).toBe("09:30");
    expect(normalizeTime("9")).toBe("09");
    expect(normalizeTime("")).toBe("");
    expect(normalizeTime("12:345")).toBe("12:34");
  });

  it("проверяет формат ЧЧ:ММ", () => {
    expect(isValidTime("09:30")).toBe(true);
    expect(isValidTime("23:59")).toBe(true);
    expect(isValidTime("24:00")).toBe(false);
    expect(isValidTime("9:30")).toBe(false);
    expect(isValidTime("09-30")).toBe(false);
  });
});
