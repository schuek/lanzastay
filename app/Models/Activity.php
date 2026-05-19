<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'image_url',
    ];

    protected $appends = [
        'max_capacity',
        'plazas_disponibles',
        'acceso_libre',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'price' => 'decimal:2',
        'max_seats' => 'integer',
    ];

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class);
    }

    /**
     * Alias de max_seats para la API y el frontend (aforo máximo; null = ilimitado).
     */
    protected function maxCapacity(): Attribute
    {
        return Attribute::get(fn () => $this->attributes['max_seats'] !== null
            ? (int) $this->attributes['max_seats']
            : null);
    }

    /**
     * Plazas libres calculadas: max_capacity − reservas pendientes/confirmadas.
     * null = acceso libre (sin límite de aforo).
     */
    protected function plazasDisponibles(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->tieneAforoLimitado()) {
                return null;
            }

            return max(0, (int) $this->max_capacity - $this->plazasOcupadas());
        });
    }

    protected function accesoLibre(): Attribute
    {
        return Attribute::get(fn () => ! $this->tieneAforoLimitado());
    }

    public function tieneAforoLimitado(): bool
    {
        return $this->attributes['max_seats'] !== null;
    }

    public function plazasOcupadas(): int
    {
        return (int) $this->activityReservations()
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->sum('seats_booked');
    }

    /** @deprecated El cupo se calcula dinámicamente; no decrementar columna. */
    public function sincronizarCupoDisponible(): void
    {
        // Sin operación: compatibilidad con código legado.
    }

    /** @deprecated El cupo se calcula dinámicamente; no decrementar columna. */
    public function decrementarCupo(int $plazas): void
    {
        // Sin operación: compatibilidad con código legado.
    }

    /** @deprecated Use el accessor plazas_disponibles */
    public function cupoDisponible(): ?int
    {
        return $this->plazas_disponibles;
    }
}
