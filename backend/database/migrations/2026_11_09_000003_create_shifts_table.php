<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_template_id')
                ->nullable()
                ->constrained('shift_templates')
                ->nullOnDelete();
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('color_key', 20)->default('cornflower');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date', 'start_time']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
