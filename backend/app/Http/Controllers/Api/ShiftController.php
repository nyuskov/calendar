<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $shifts = Shift::query()
            ->whereBetween('work_date', [$data['from'], $data['to']])
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => $shifts->map(fn (Shift $shift) => $this->present($shift))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'start_time' => ['required_without:template_id', 'date_format:H:i'],
            'end_time' => ['required_without:template_id', 'date_format:H:i'],
        ]);

        [$start, $end, $template, $color] = $this->resolveTimes($data);

        $shift = Shift::firstOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'work_date' => $data['date'],
                'start_time' => $start,
            ],
            [
                'shift_template_id' => $template?->id,
                'end_time' => $end,
                'color_key' => $color,
            ],
        );

        return response()->json(['data' => $this->present($shift)], 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Точечные ячейки: выделение в календаре может быть непрямоугольным.
            'cells' => ['nullable', 'array', 'min:1'],
            'cells.*.employee_id' => ['required_with:cells', 'integer', 'exists:employees,id'],
            'cells.*.date' => ['required_with:cells', 'date_format:Y-m-d'],
            'employee_ids' => ['required_without:cells', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'dates' => ['required_without:cells', 'array', 'min:1'],
            'dates.*' => ['date_format:Y-m-d'],
            'template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'start_time' => ['required_without:template_id', 'date_format:H:i'],
            'end_time' => ['required_without:template_id', 'date_format:H:i'],
        ]);

        [$start, $end, $template, $color] = $this->resolveTimes($data);

        $pairs = $this->resolvePairs($data);

        $shifts = DB::transaction(function () use ($pairs, $start, $end, $template, $color) {
            $result = [];

            foreach ($pairs as $pair) {
                $result[] = Shift::firstOrCreate(
                    [
                        'employee_id' => $pair['employee_id'],
                        'work_date' => $pair['date'],
                        'start_time' => $start,
                    ],
                    [
                        'shift_template_id' => $template?->id,
                        'end_time' => $end,
                        'color_key' => $color,
                    ],
                );
            }

            return collect($result);
        });

        return response()->json([
            'data' => $shifts->map(fn (Shift $shift) => $this->present($shift))->values(),
        ], 201);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
        ]);

        if (! empty($data['template_id'])) {
            $template = ShiftTemplate::findOrFail($data['template_id']);

            $shift->fill([
                'shift_template_id' => $template->id,
                'start_time' => $template->start_time,
                'end_time' => $template->end_time,
                'color_key' => $template->color_key,
            ]);
        } elseif (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $shift->fill([
                'shift_template_id' => null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'color_key' => 'brand',
            ]);
        }

        $shift->save();

        return response()->json(['data' => $this->present($shift)]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $id = $shift->id;
        $shift->delete();

        return response()->json(['data' => ['id' => $id]]);
    }

    public function coverage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $rows = Shift::query()
            ->selectRaw('work_date, count(*) as total')
            ->whereBetween('work_date', [$data['from'], $data['to']])
            ->groupBy('work_date')
            ->orderBy('work_date')
            ->get();

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'date' => substr((string) $row->work_date, 0, 10),
                'count' => (int) $row->total,
            ]),
        ]);
    }

    /**
     * @return array<int, array{employee_id: int, date: string}>
     */
    private function resolvePairs(array $data): array
    {
        if (! empty($data['cells'])) {
            $pairs = [];

            foreach ($data['cells'] as $cell) {
                $pairs[$cell['employee_id'].':'.$cell['date']] = [
                    'employee_id' => (int) $cell['employee_id'],
                    'date' => $cell['date'],
                ];
            }

            return array_values($pairs);
        }

        $pairs = [];

        foreach (array_unique($data['employee_ids']) as $employeeId) {
            foreach (array_unique($data['dates']) as $date) {
                $pairs[$employeeId.':'.$date] = [
                    'employee_id' => (int) $employeeId,
                    'date' => $date,
                ];
            }
        }

        return array_values($pairs);
    }

    /**
     * @return array{0: string, 1: string, 2: ShiftTemplate|null, 3: string}
     */
    private function resolveTimes(array $data): array
    {
        if (! empty($data['template_id'])) {
            $template = ShiftTemplate::findOrFail($data['template_id']);

            return [
                substr((string) $template->start_time, 0, 5),
                substr((string) $template->end_time, 0, 5),
                $template,
                $template->color_key,
            ];
        }

        return [$data['start_time'], $data['end_time'], null, 'brand'];
    }

    private function present(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'employee_id' => $shift->employee_id,
            'shift_template_id' => $shift->shift_template_id,
            'date' => $shift->work_date->format('Y-m-d'),
            'start_time' => substr((string) $shift->start_time, 0, 5),
            'end_time' => substr((string) $shift->end_time, 0, 5),
            'duration_hours' => $shift->duration_hours,
            'color_key' => $shift->color_key,
        ];
    }
}
