<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        Activity::create([
            'name' => 'Bus Timanfaya',
            'description' => 'Excursión completa al Parque Nacional.',
            'type' => 'bus_tour',
            'date_time' => now()->addDays(2),
            'price' => 25.00,
            'max_seats' => 50,
            'image_url' => 'https://example.com/timanfaya.avif'
        ]);

        Activity::create([
            'name' => 'Yoga al amanecer',
            'description' => 'Sesión de relax frente al mar.',
            'type' => 'hotel_activity',
            'date_time' => now()->addHours(5),
            'price' => 0.00,
            'max_seats' => 15,
            'image_url' => 'https://example.com/yoga.avif'
        ]);
    }
}