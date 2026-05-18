<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Habitacion extends Model
{
    protected $fillable = [
        'numero',
        'activa',
        'status',
        'access_token',
        'current_session_token',
        'guest_email',
        'precio_noche',
        'check_in_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Habitacion $habitacion): void {
            if (empty($habitacion->access_token)) {
                $habitacion->access_token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'precio_noche' => 'decimal:2',
            'check_in_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'habitacion_id');
    }

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class, 'room_id');
    }

    public function getNumberAttribute(): string
    {
        return (string) $this->numero;
    }

    public function generateQrUrl(): string
    {
        // 🚀 SALVAGUARDA: Si la habitación no tiene token, devolvemos una cadena vacía en vez de romper la web
        if (empty($this->access_token)) {
            return '';
        }

        return route('menu.show', ['habitacion' => $this->access_token]);
    }

    /**
     * Pedidos visibles para el huésped: vacío si la habitación no está ocupada;
     * si no, los 20 más recientes de esta habitación (sin filtro por fecha de check-in).
     */
    public function ordersForCurrentStay(): Collection
    {
        if ($this->status !== 'ocupada') {
            return collect();
        }

        return $this->orders()
            ->with('services')
            ->latest()
            ->take(20)
            ->get();
    }
}
