<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('categoria_restaurante')->nullable()->after('service_category');
            $table->string('horario')->nullable()->after('categoria_restaurante');
        });

        DB::table('services')
            ->where('service_type', 'comida')
            ->update([
                'categoria_restaurante' => DB::raw("
                    CASE
                        WHEN LOWER(service_category) = 'bebida' THEN 'Bebida'
                        WHEN LOWER(service_category) = 'postre' THEN 'Postre'
                        ELSE 'Comida'
                    END
                "),
                'horario' => 'Todo el dia',
            ]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['categoria_restaurante', 'horario']);
        });
    }
};
