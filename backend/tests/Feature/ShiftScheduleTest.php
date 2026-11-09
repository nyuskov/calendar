<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_employees_can_be_searched_by_fio(): void
    {
        $response = $this->getJson('/api/employees?search=волк');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Волков Тимур');
    }

    public function test_employees_are_returned_in_maket_order(): void
    {
        $response = $this->getJson('/api/employees');

        $response->assertOk()->assertJsonCount(20, 'data');
        $response->assertJsonPath('data.0.full_name', 'Кузнецова Алина');
        $response->assertJsonPath('data.7.full_name', 'Гаврилов Егор');
        $response->assertJsonPath('data.19.full_name', 'Алексеев Глеб');
    }

    public function test_bulk_assignment_creates_a_shift_for_every_cell(): void
    {
        $employee = Employee::query()->orderBy('sort_order')->firstOrFail();
        $morning = ShiftTemplate::query()->where('code', 'morning')->firstOrFail();

        $response = $this->postJson('/api/shifts/bulk', [
            'template_id' => $morning->id,
            'cells' => [
                ['employee_id' => $employee->id, 'date' => '2026-11-09'],
                ['employee_id' => $employee->id, 'date' => '2026-11-10'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data')
            // Время отдаётся в формате H:i.
            ->assertJsonPath('data.0.start_time', '07:00')
            ->assertJsonPath('data.0.end_time', '15:00');

        $this->assertDatabaseHas('shifts', [
            'employee_id' => $employee->id,
            'color_key' => 'cornflower',
        ]);

        $this->assertTrue(
            Shift::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', '2026-11-09')
                ->exists(),
        );
    }

    public function test_custom_shift_duration_handles_overnight(): void
    {
        $employee = Employee::query()->firstOrFail();

        $response = $this->postJson('/api/shifts', [
            'employee_id' => $employee->id,
            'date' => '2026-11-09',
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.duration_hours', 8)
            ->assertJsonPath('data.color_key', 'brand');
    }

    public function test_shift_can_be_updated_and_deleted(): void
    {
        $employee = Employee::query()->firstOrFail();
        $day = ShiftTemplate::query()->where('code', 'day')->firstOrFail();

        $created = $this->postJson('/api/shifts', [
            'employee_id' => $employee->id,
            'date' => '2026-11-09',
            'template_id' => $day->id,
        ])->assertCreated()->json('data');

        $this->patchJson("/api/shifts/{$created['id']}", [
            'start_time' => '09:30',
            'end_time' => '19:30',
        ])
            ->assertOk()
            ->assertJsonPath('data.start_time', '09:30')
            ->assertJsonPath('data.duration_hours', 10)
            ->assertJsonPath('data.shift_template_id', null);

        $this->deleteJson("/api/shifts/{$created['id']}")->assertOk();
        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_coverage_counts_shifts_per_date(): void
    {
        $employees = Employee::query()->orderBy('sort_order')->limit(2)->get();
        $day = ShiftTemplate::query()->where('code', 'day')->firstOrFail();

        $this->postJson('/api/shifts/bulk', [
            'template_id' => $day->id,
            'cells' => [
                ['employee_id' => $employees[0]->id, 'date' => '2026-11-09'],
                ['employee_id' => $employees[1]->id, 'date' => '2026-11-09'],
                ['employee_id' => $employees[0]->id, 'date' => '2026-11-10'],
            ],
        ])->assertCreated();

        $this->getJson('/api/coverage?from=2026-11-09&to=2026-11-10')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['date' => '2026-11-09', 'count' => 2],
                    ['date' => '2026-11-10', 'count' => 1],
                ],
            ]);
    }

    public function test_shift_requires_valid_time(): void
    {
        $employee = Employee::query()->firstOrFail();

        $this->postJson('/api/shifts', [
            'employee_id' => $employee->id,
            'date' => '2026-11-09',
            'start_time' => '25:00',
            'end_time' => '08:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('start_time');
    }
}
