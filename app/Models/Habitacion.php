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
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'habitacion_id');
    }
}
