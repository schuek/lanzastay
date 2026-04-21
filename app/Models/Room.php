<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $table = 'habitacions';

    protected $fillable = [
        'numero',
        'activa',
        'status',
        'current_session_token',
    ];

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class, 'room_id');
    }
}
