<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'role',
        'weekly_hours',
        'avatar_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weekly_hours' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_name', 'initials'];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            $employee->search_key = mb_strtolower(trim(implode(' ', array_filter([
                $employee->last_name,
                $employee->first_name,
                $employee->middle_name,
            ]))));
        });
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
            $this->middle_name,
        ])));
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(
            mb_substr($this->last_name, 0, 1).mb_substr($this->first_name, 0, 1)
        );
    }
}
