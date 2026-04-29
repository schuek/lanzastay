<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaActividad extends Model
{
    protected $table = 'reservas_actividades';

    protected $fillable = [
        'habitacion_id',
        'actividad_id',
        'email_cliente',
        'titulo_actividad',
        'horario_actividad',
        'num_personas',
        'precio_total',
        'fecha',
    ];

    protected $casts = [
        'precio_total' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'actividad_id');
    }
}
