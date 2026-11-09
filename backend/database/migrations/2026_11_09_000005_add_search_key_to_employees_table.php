<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Нормализованное ФИО: lower() в SQLite не понимает кириллицу,
            // поэтому поиск идёт по заранее приведённой строке.
            $table->string('search_key', 255)->default('')->after('middle_name');
            $table->index('search_key');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['search_key']);
            $table->dropColumn('search_key');
        });
    }
};
