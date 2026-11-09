import { describe, expect, it } from "vitest";
import { cellKey, parseCell } from "../src/composables/useSchedule";

describe("ключи ячеек", () => {
  it("склеивает сотрудника и дату", () => {
    expect(cellKey(5, "2026-11-09")).toBe("5:2026-11-09");
  });

  it("разбирает корректный ключ", () => {
    expect(parseCell("5:2026-11-09")).toEqual({
      employeeId: 5,
      date: "2026-11-09",
    });
  });

  it("возвращает null для некорректного ключа", () => {
    expect(parseCell("broken")).toBeNull();
    expect(parseCell(":")).toBeNull();
  });
});
