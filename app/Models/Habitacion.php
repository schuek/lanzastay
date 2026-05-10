<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habitacion extends Model
{
    protected $fillable = [
        'numero',
        'activa',
        'status',
        'current_session_token',
        'guest_email',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'habitacion_id');
    }

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class, 'room_id');
    }

    public function generateQrUrl(): string
    {
        return rtrim(config('app.url'), '/').'/menu/'.$this->numero;
    }
}
