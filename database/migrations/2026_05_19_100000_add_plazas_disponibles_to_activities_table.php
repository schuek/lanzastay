<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('plazas_disponibles')->default(0)->after('max_seats');
        });

        Activity::query()->each(function (Activity $activity): void {
            $reservadas = (int) DB::table('activity_reservations')
                ->where('activity_id', $activity->id)
                ->where('status', 'confirmada')
                ->sum('seats_booked');

            $disponibles = max(0, (int) $activity->max_seats - $reservadas);

            $activity->forceFill(['plazas_disponibles' => $disponibles])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('plazas_disponibles');
        });
    }
};
