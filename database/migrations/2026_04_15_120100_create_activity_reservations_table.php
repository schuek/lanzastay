<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('habitacions')->cascadeOnDelete();
            $table->string('session_token', 80);
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seats_booked');
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pendiente', 'confirmada', 'cancelada'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reservations');
    }
};
