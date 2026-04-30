<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    // Campos que permitimos rellenar
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'service_type',
        'service_category',
        'ingredients',
        'is_vegan',
        'image_url',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'is_vegan' => 'boolean',
    ];

    // Relación: Un Servicio "pertenece a" una Categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
