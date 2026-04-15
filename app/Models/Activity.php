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
        'image_url',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function activityReservations(): HasMany
    {
        return $this->hasMany(ActivityReservation::class);
    }
}
