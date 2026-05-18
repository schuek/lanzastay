<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media')->after('status');
            $table->text('notas_internas')->nullable()->after('description');
            $table->text('notas_resolucion')->nullable()->after('notas_internas');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['prioridad', 'notas_internas', 'notas_resolucion']);
        });
    }
};
