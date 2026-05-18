<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'date_time',
        'price',
        'max_seats',
        'plazas_disponibles',
        'image_url',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'price' => 'decimal:2',
        'max_seats' => 'integer',
        'plazas_disponibles' => 'integer',
    ];

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class);
    }

    /**
     * Cupo libre efectivo (columna plazas_disponibles, con respaldo desde max_seats).
     */
    public function cupoDisponible(): int
    {
        $maxSeats = max(0, (int) $this->max_seats);
        $almacenado = $this->getAttribute('plazas_disponibles');

        if ($almacenado === null) {
            return $this->cupoCalculadoDesdeReservas($maxSeats);
        }

        $enColumna = max(0, (int) $almacenado);
        $calculado = $this->cupoCalculadoDesdeReservas($maxSeats);

        // Datos legacy: columna en 0 pero max_seats aún tiene cupo sin reservas confirmadas.
        if ($enColumna === 0 && $calculado > 0) {
            return $calculado;
        }

        return $enColumna;
    }

    /**
     * Persiste plazas_disponibles cuando el valor almacenado no refleja el cupo real.
     */
    public function sincronizarCupoDisponible(): void
    {
        $maxSeats = max(0, (int) $this->max_seats);
        $calculado = $this->cupoCalculadoDesdeReservas($maxSeats);
        $almacenado = $this->getAttribute('plazas_disponibles');

        if ($almacenado === null || ((int) $almacenado === 0 && $calculado > 0)) {
            $this->forceFill(['plazas_disponibles' => $calculado])->saveQuietly();
        }
    }

    public function decrementarCupo(int $plazas): void
    {
        $this->decrement('plazas_disponibles', $plazas);
    }

    protected function cupoCalculadoDesdeReservas(int $maxSeats): int
    {
        $reservadas = (int) $this->activityReservations()
            ->where('status', 'confirmada')
            ->sum('seats_booked');

        return max(0, $maxSeats - $reservadas);
    }
}
