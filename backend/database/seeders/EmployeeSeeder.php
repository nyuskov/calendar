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
            ['last_name' => 'Тихонова', 'first_name' => 'Мария', 'avatar_color' => '#D4599B'],
            ['last_name' => 'Белов', 'first_name' => 'Максим', 'avatar_color' => '#3E87FF'],
            ['last_name' => 'Киселёва', 'first_name' => 'Анна', 'avatar_color' => '#9B51E0'],
            ['last_name' => 'Орлов', 'first_name' => 'Дмитрий', 'avatar_color' => '#2D9CDB'],
            ['last_name' => 'Павлова', 'first_name' => 'Софья', 'avatar_color' => '#EB5757'],
            ['last_name' => 'Зайцев', 'first_name' => 'Никита', 'avatar_color' => '#27AE60'],
            ['last_name' => 'Миронова', 'first_name' => 'Елена', 'avatar_color' => '#F2994A'],
            ['last_name' => 'Фёдоров', 'first_name' => 'Илья', 'avatar_color' => '#56CCF2'],
            ['last_name' => 'Крылова', 'first_name' => 'Вера', 'avatar_color' => '#BB6BD9'],
            ['last_name' => 'Шестаков', 'first_name' => 'Павел', 'avatar_color' => '#6FCF97'],
            ['last_name' => 'Романова', 'first_name' => 'Ольга', 'avatar_color' => '#F2C94C'],
            ['last_name' => 'Алексеев', 'first_name' => 'Глеб', 'avatar_color' => '#4F4F4F'],
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
