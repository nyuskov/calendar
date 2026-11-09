<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftApiTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private ShiftTemplate $morning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->employee = Employee::query()->orderBy('sort_order')->firstOrFail();
        $this->morning = ShiftTemplate::query()->where('code', 'morning')->firstOrFail();
    }

    public function test_templates_are_returned_in_display_order(): void
    {
        $this->getJson('/api/shift-templates')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Утро')
            ->assertJsonPath('data.1.name', 'День')
            ->assertJsonPath('data.2.name', 'Вечер')
            ->assertJsonPath('data.2.code', 'evening');

        $this->assertSame(0.0, ShiftTemplate::durationBetween(null, '15:00'));
        $this->assertSame(9.0, ShiftTemplate::durationBetween('23:00', '08:00'));
    }

    public function test_shifts_are_filtered_by_period_inclusively(): void
    {
        $this->bulk($this->morning->id, [$this->employee->id], ['2026-11-09', '2026-11-12']);

        $this->getJson('/api/shifts?from=2026-11-09&to=2026-11-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-11-09');
    }

    public function test_shift_can_be_assigned_with_single_request(): void
    {
        $this->postJson('/api/shifts', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-11',
            'template_id' => $this->morning->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.start_time', '07:00')
            ->assertJsonPath('data.end_time', '15:00')
            ->assertJsonPath('data.color_key', 'cornflower');

        $shift = Shift::query()->firstOrFail();

        $this->assertSame($this->employee->id, $shift->employee->id);
        $this->assertSame($this->morning->id, $shift->template->id);
    }

    public function test_repeated_assignment_does_not_create_duplicates(): void
    {
        $payload = [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-09',
            'template_id' => $this->morning->id,
        ];

        $first = $this->postJson('/api/shifts', $payload)->assertCreated()->json('data');
        $second = $this->postJson('/api/shifts', $payload)->assertCreated()->json('data');

        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_bulk_assignment_accepts_employee_ids_and_dates(): void
    {
        $employees = Employee::query()->orderBy('sort_order')->limit(2)->pluck('id')->all();

        $this->postJson('/api/shifts/bulk', [
            'employee_ids' => $employees,
            'dates' => ['2026-11-09'],
            'template_id' => $this->morning->id,
        ])->assertCreated()->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('shifts', 2);
    }

    public function test_patch_switches_shift_to_another_template(): void
    {
        $shift = $this->postJson('/api/shifts', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ])->assertCreated()->json('data');

        $day = ShiftTemplate::query()->where('code', 'day')->firstOrFail();

        $this->patchJson("/api/shifts/{$shift['id']}", ['template_id' => $day->id])
            ->assertOk()
            ->assertJsonPath('data.start_time', '10:00')
            ->assertJsonPath('data.end_time', '18:00')
            ->assertJsonPath('data.color_key', 'mint')
            ->assertJsonPath('data.shift_template_id', $day->id);
    }

    public function test_employee_and_template_expose_related_shifts(): void
    {
        $this->bulk($this->morning->id, [$this->employee->id], ['2026-11-09']);

        $employee = Employee::query()->firstOrFail();

        $this->assertSame('КА', $employee->initials);
        $this->assertSame('Кузнецова Алина', $employee->full_name);
        $this->assertCount(1, $employee->shifts);

        $template = ShiftTemplate::query()->where('code', 'morning')->firstOrFail();

        $this->assertCount(1, $template->shifts);
    }

    public function test_missing_shift_returns_404(): void
    {
        $this->patchJson('/api/shifts/999999', [
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertNotFound();
    }

    public function test_store_rejects_unknown_employee(): void
    {
        $this->postJson('/api/shifts', [
            'employee_id' => 999999,
            'date' => '2026-11-09',
            'template_id' => $this->morning->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('employee_id');
    }

    public function test_store_rejects_invalid_date(): void
    {
        $this->postJson('/api/shifts', [
            'employee_id' => $this->employee->id,
            'date' => '2026-13-01',
            'template_id' => $this->morning->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('date');
    }

    public function test_custom_shift_rejects_equal_start_and_end(): void
    {
        $this->postJson('/api/shifts', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-09',
            'start_time' => '09:00',
            'end_time' => '09:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_time');
    }

    public function test_bulk_requires_cells_or_lists(): void
    {
        $this->postJson('/api/shifts/bulk', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_ids', 'dates', 'start_time', 'end_time']);
    }

    public function test_search_returns_empty_list_for_unknown_name(): void
    {
        $this->getJson('/api/employees?search=zzz')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_coverage_returns_empty_list_for_period_without_shifts(): void
    {
        $this->getJson('/api/coverage?from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @param  array<int, string>  $dates
     */
    private function bulk(int $templateId, array $employeeIds, array $dates): void
    {
        $this->postJson('/api/shifts/bulk', [
            'template_id' => $templateId,
            'employee_ids' => $employeeIds,
            'dates' => $dates,
        ])->assertCreated();
    }
}
