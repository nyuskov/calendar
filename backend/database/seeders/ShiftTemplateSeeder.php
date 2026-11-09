<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Утро',
                'code' => 'morning',
                'start_time' => '07:00',
                'end_time' => '15:00',
                'color_key' => 'cornflower',
                'sort_order' => 1,
            ],
            [
                'name' => 'День',
                'code' => 'day',
                'start_time' => '10:00',
                'end_time' => '18:00',
                'color_key' => 'mint',
                'sort_order' => 2,
            ],
            [
                'name' => 'Вечер',
                'code' => 'evening',
                'start_time' => '15:00',
                'end_time' => '23:00',
                'color_key' => 'lavanda',
                'sort_order' => 3,
            ],
        ];

        foreach ($templates as $template) {
            ShiftTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template + ['is_active' => true],
            );
        }
    }
}
