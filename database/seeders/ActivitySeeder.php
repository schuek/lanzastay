<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use Carbon\Carbon;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Parque Nacional de Timanfaya',
                'type' => 'bus_tour',
                'price' => 45.00,
                'max_seats' => 40,
                'date_time' => Carbon::now()->addDays(1)->setTime(9, 30),
                'image_url' => '/images/timanfaya.avif'
            ],
            [
                'name' => 'Jameos del Agua',
                'type' => 'bus_tour',
                'price' => 35.00,
                'max_seats' => 30,
                'date_time' => Carbon::now()->addDays(2)->setTime(10, 00),
                'image_url' => '/images/jameos.avif'
            ],
            [
                'name' => 'Cueva de los Verdes',
                'type' => 'bus_tour',
                'price' => 30.00,
                'max_seats' => 25,
                'date_time' => Carbon::now()->addDays(3)->setTime(11, 00),
                'image_url' => '/images/cuevadelosverdes.avif'
            ],
            [
                'name' => 'Mirador del Río',
                'type' => 'bus_tour',
                'price' => 25.00,
                'max_seats' => 20,
                'date_time' => Carbon::now()->addDays(4)->setTime(12, 30),
                'image_url' => '/images/miradordelrio.avif'
            ],
            [
                'name' => 'Ruta de Vinos por La Geria',
                'type' => 'bus_tour',
                'price' => 40.00,
                'max_seats' => 20,
                'date_time' => Carbon::now()->addDays(5)->setTime(16, 00),
                'image_url' => '/images/lageria.avif'
            ],
            [
                'name' => 'Fundación César Manrique',
                'type' => 'bus_tour',
                'price' => 28.00,
                'max_seats' => 35,
                'date_time' => Carbon::now()->addDays(6)->setTime(10, 00),
                'image_url' => null // Dejamos esta vacía para que veas que el fondo elegante funciona
            ]
        ];

        foreach ($activities as $activity) {
            Activity::create($activity);
        }
    }
}
