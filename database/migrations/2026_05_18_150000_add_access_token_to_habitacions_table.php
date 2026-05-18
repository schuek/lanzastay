<?php

use App\Models\Habitacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->uuid('access_token')->nullable()->unique()->after('id');
        });

        Habitacion::query()
            ->whereNull('access_token')
            ->orderBy('id')
            ->each(function (Habitacion $habitacion): void {
                $habitacion->forceFill([
                    'access_token' => (string) Str::uuid(),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn('access_token');
        });
    }
};
