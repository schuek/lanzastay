<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla PEDIDOS
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('room_number'); // Ej: "101"
            $table->decimal('total_price', 8, 2);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        // 2. Tabla intermedia (PLATOS DEL PEDIDO)
        Schema::create('order_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_service');
        Schema::dropIfExists('orders');
    }
};
