<?php

namespace App\Models;

use App\Support\AmenityRequestType;
use App\Support\CleaningRequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    // Permitimos que se guarden estos datos en masa
    public const PRIORIDAD_BAJA = 'baja';

    public const PRIORIDAD_MEDIA = 'media';

    public const PRIORIDAD_ALTA = 'alta';

    protected $fillable = [
        'habitacion_id',
        'room_number',
        'session_token',
        'guest_email',
        'service_type',
        'requested_time',
        'description',
        'notas',
        'notas_internas',
        'notas_resolucion',
        'prioridad',
        'total_price',
        'status',
    ];

    protected $attributes = [
        'prioridad' => self::PRIORIDAD_MEDIA,
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

    /**
     * Solo tareas de housekeeping: amenities de baño y limpieza de habitación.
     * Excluye pedidos de cocina (agua, bocadillo) aunque estén mal clasificados en BD.
     */
    public function scopeCleaningBoard(Builder $query): Builder
    {
        return $query
            ->where('service_type', 'limpieza')
            ->whereNotIn('description', AmenityRequestType::restaurantCodes())
            ->where(function (Builder $q): void {
                $q->whereIn('description', CleaningRequestType::allowedDescriptions())
                    ->orWhereNull('description')
                    ->orWhere('description', '');
            });
    }
}
