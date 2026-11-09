<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_template_id',
        'work_date',
        'start_time',
        'end_time',
        'color_key',
    ];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
    ];

    protected $appends = ['duration_hours'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function getDurationHoursAttribute(): float
    {
        return ShiftTemplate::durationBetween($this->start_time, $this->end_time);
    }
}
