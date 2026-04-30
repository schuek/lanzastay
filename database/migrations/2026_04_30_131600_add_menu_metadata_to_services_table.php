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
            $table->string('service_category')->default('Comida')->after('service_type');
            $table->json('ingredients')->nullable()->after('service_category');
            $table->boolean('is_vegan')->default(false)->after('ingredients');
        });

        DB::table('services')
            ->leftJoin('categories', 'services.category_id', '=', 'categories.id')
            ->update([
                'services.service_category' => DB::raw("
                    CASE
                        WHEN LOWER(categories.name) LIKE '%bebida%' THEN 'Bebida'
                        WHEN LOWER(categories.name) LIKE '%postre%' THEN 'Postre'
                        WHEN LOWER(categories.name) LIKE '%limpieza%' THEN 'Limpieza'
                        WHEN LOWER(categories.name) LIKE '%mantenimiento%' THEN 'Mantenimiento'
                        WHEN LOWER(services.service_type) = 'limpieza' THEN 'Limpieza'
                        WHEN LOWER(services.service_type) = 'mantenimiento' THEN 'Mantenimiento'
                        ELSE 'Comida'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['service_category', 'ingredients', 'is_vegan']);
        });
    }
};
