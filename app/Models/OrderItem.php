<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'service_id', 'quantity', 'price_at_time'];

    // Pertenece al pedido general
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Se refiere a un servicio del menú (ej: Hamburguesa)
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
