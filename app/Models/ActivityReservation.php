<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'session_token',
        'activity_id',
        'seats_booked',
        'scheduled_time',
        'total_price',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'room_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
