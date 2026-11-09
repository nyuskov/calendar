import {
  expect,
  test,
  type APIRequestContext,
  type Page,
} from "@playwright/test";

interface Shift {
  id: number;
  employee_id: number;
  date: string;
}

interface Employee {
  id: number;
  full_name: string;
}

function formatDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function weekRange(): { from: string; to: string } {
  const today = new Date();
  const offset = (today.getDay() + 6) % 7;
  const monday = new Date(today);
  monday.setDate(today.getDate() - offset);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);

  return { from: formatDate(monday), to: formatDate(sunday) };
}

async function clearWeekShifts(request: APIRequestContext): Promise<void> {
  const { from, to } = weekRange();
  const response = await request.get(`/api/shifts?from=${from}&to=${to}`);
  const body = (await response.json()) as { data: Shift[] };

  for (const shift of body.data) {
    await request.delete(`/api/shifts/${shift.id}`);
  }
}

async function openSchedule(page: Page): Promise<void> {
  await page.goto("/");
  // Ждём, пока загрузятся сотрудники и шаблоны.
  await expect(page.locator(".employee-cell__name")).toHaveCount(8);
  await expect(page.locator(".template-card")).toHaveCount(3);
}

test.beforeEach(async ({ request }) => {
  await clearWeekShifts(request);
});

test.afterEach(async ({ request }) => {
  await clearWeekShifts(request);
});

test("открывает график с сотрудниками, шаблонами и пустым покрытием", async ({
  page,
}) => {
  await openSchedule(page);

  await expect(page.getByRole("heading", { name: "График" })).toBeVisible();
  await expect(page.locator(".employee-cell__name").first()).toHaveText(
    "Кузнецова Алина",
  );
  await expect(page.locator(".coverage__pill--empty")).toHaveCount(7);
});

test("назначает шаблон на две выделенные ячейки", async ({ page, request }) => {
  await openSchedule(page);

  await page.locator(".cell__inner").nth(0).click();
  await page.locator(".cell__inner").nth(1).click();
  await expect(page.locator(".selection__text")).toContainText("2 ячеек");

  await page.locator(".template-card").first().click();

  await expect(page.locator(".chip")).toHaveCount(2);
  await expect(page.locator(".chip").first()).toContainText("07 - 15");
  await expect(page.locator(".coverage__pill").nth(0)).toHaveText("1");
  await expect(page.locator(".coverage__pill").nth(1)).toHaveText("1");
  await expect(page.locator(".coverage__pill").nth(2)).toHaveText("0");

  const { from, to } = weekRange();
  const shifts = await request.get(`/api/shifts?from=${from}&to=${to}`);
  const body = (await shifts.json()) as { data: Shift[] };

  expect(body.data).toHaveLength(2);
});

test("создаёт свою смену, меняет время и удаляет её", async ({ page }) => {
  await openSchedule(page);

  await page.locator(".cell__inner").nth(7).click();
  await page.fill("#custom-start", "10:00");
  await page.fill("#custom-end", "20:00");
  await page.getByRole("button", { name: "Поставить в выделенные" }).click();

  const chip = page.locator(".chip").first();
  await expect(chip).toContainText("10 - 20");
  await expect(chip).toContainText("10 ч");

  await chip.click();
  await page.getByRole("button", { name: "Изменить время" }).click();
  await page.fill("#custom-start", "08:00");
  await page.fill("#custom-end", "18:00");
  await page
    .locator(".panel--side")
    .getByRole("button", { name: "Изменить", exact: true })
    .click();

  await expect(page.locator(".chip").first()).toContainText("08 - 18");

  await page.locator(".chip").first().click();
  await page.getByRole("button", { name: "Удалить" }).click();

  await expect(page.locator(".chip")).toHaveCount(0);
  await expect(page.locator(".coverage__pill--empty")).toHaveCount(7);
});

test("фильтрует сотрудников поиском по ФИО", async ({ page }) => {
  await openSchedule(page);

  await page.locator(".search input").fill("волк");
  await expect(page.locator(".employee-cell__name")).toHaveCount(1);
  await expect(page.locator(".employee-cell__name")).toHaveText("Волков Тимур");

  await page.locator(".search input").fill("");
  await expect(page.locator(".employee-cell__name")).toHaveCount(8);
});

test("показывает месячный вид и покрытие после назначения", async ({
  page,
}) => {
  await openSchedule(page);

  await page.locator(".cell__inner").nth(0).click();
  await page.locator(".template-card").nth(1).click();
  await expect(page.locator(".chip")).toHaveCount(1);

  await page.getByRole("button", { name: "Месяц" }).click();
  await expect(page.locator(".schedule__head-cell--days")).toHaveCount(30);
  await expect(page.locator(".chip").first()).toContainText("8 ч");
});
