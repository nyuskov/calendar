<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'color_key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['duration_hours'];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'shift_template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDurationHoursAttribute(): float
    {
        return self::durationBetween($this->start_time, $this->end_time);
    }

    /**
     * Длительность смены в часах. Поддерживает переход через полночь.
     */
    public static function durationBetween(?string $start, ?string $end): float
    {
        if (! $start || ! $end) {
            return 0.0;
        }

        [$startHours, $startMinutes] = array_pad(explode(':', $start), 2, '0');
        [$endHours, $endMinutes] = array_pad(explode(':', $end), 2, '0');

        $minutes = ((int) $endHours * 60 + (int) $endMinutes)
            - ((int) $startHours * 60 + (int) $startMinutes);

        if ($minutes <= 0) {
            $minutes += 24 * 60;
        }

        return round($minutes / 60, 1);
    }
}
