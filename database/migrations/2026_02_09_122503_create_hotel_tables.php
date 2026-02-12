<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Habitaciones (Con token para el QR)
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); // Solo necesitamos el número
            $table->timestamps();
        });

        // 2. Categorías (Comida, Bebida, Limpieza...)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable(); // Para ponerle un icono luego
            $table->timestamps();
        });

        // 3. Servicios (Hamburguesa, Coca-Cola, Toallas...)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            // Relación con Categoría
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Borrar en orden inverso
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('rooms');
    }
};
