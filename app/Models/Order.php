<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Permitimos que se guarden estos datos en masa
    protected $fillable = ['room_number',
                            'total_price',
                            'status'];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'order_service')
                    ->withPivot('quantity', 'price') // <-- ¡Importante! Para leer la cantidad y precio
                    ->withTimestamps();
    }
}
