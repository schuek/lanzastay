<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->constrained('habitacions')->cascadeOnDelete();
            $table->foreignId('actividad_id')->constrained('activities')->cascadeOnDelete();
            $table->string('email_cliente');
            $table->string('titulo_actividad');
            $table->string('horario_actividad');
            $table->unsignedInteger('num_personas');
            $table->decimal('precio_total', 10, 2)->default(0);
            $table->dateTime('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas_actividades');
    }
};
