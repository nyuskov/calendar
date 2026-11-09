<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['last_name' => 'Кузнецова', 'first_name' => 'Алина', 'avatar_color' => '#5B7DEC'],
            ['last_name' => 'Волков', 'first_name' => 'Тимур', 'avatar_color' => '#7C5CFF'],
            ['last_name' => 'Морозова', 'first_name' => 'Виктория', 'avatar_color' => '#3E87FF'],
            ['last_name' => 'Соколов', 'first_name' => 'Артём', 'avatar_color' => '#2AA79B'],
            ['last_name' => 'Лебедева', 'first_name' => 'Полина', 'avatar_color' => '#E4794A'],
            ['last_name' => 'Новиков', 'first_name' => 'Кирилл', 'avatar_color' => '#C05CB5'],
            ['last_name' => 'Ерохина', 'first_name' => 'Дарья', 'avatar_color' => '#4C9A2A'],
            ['last_name' => 'Гаврилов', 'first_name' => 'Егор', 'avatar_color' => '#8A6BE8'],
        ];

        foreach ($employees as $index => $employee) {
            Employee::updateOrCreate(
                ['last_name' => $employee['last_name'], 'first_name' => $employee['first_name']],
                $employee + [
                    'role' => 'агент',
                    'weekly_hours' => 24,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
