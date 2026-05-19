<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('max_seats')->nullable()->change();
            $table->unsignedInteger('plazas_disponibles')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('max_seats')->nullable(false)->change();
            $table->unsignedInteger('plazas_disponibles')->nullable(false)->default(0)->change();
        });
    }
};
