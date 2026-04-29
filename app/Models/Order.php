<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    // Permitimos que se guarden estos datos en masa
    protected $fillable = [
        'habitacion_id',
        'room_number',
        'session_token',
        'guest_email',
        'service_type',
        'requested_time',
        'description',
        'total_price',
        'status',
    ];

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'order_service')
                    ->withPivot('quantity', 'price') // <-- ¡Importante! Para leer la cantidad y precio
                    ->withTimestamps();
    }
}
