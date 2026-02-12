<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Service;
use App\Models\Room;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. CREAMOS LAS TRES GRANDES ÁREAS ---
        $restaurante = Category::create(['name' => 'Restaurante', 'icon' => 'CakeIcon']);
        $limpieza = Category::create(['name' => 'Limpieza', 'icon' => 'SparklesIcon']);
        $mantenimiento = Category::create(['name' => 'Mantenimiento', 'icon' => 'WrenchScrewdriverIcon']);

        // --- 2. SERVICIOS
        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Hamburguesa LanzaStay',
            'description' => 'Completa con queso y bacon.',
            'price' => 12.50,
        ]);
        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Mojito Cubano',
            'description' => 'Ron, lima y hierbabuena.',
            'price' => 7.50,
        ]);

        Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Pack Toallas Extra',
            'description' => 'Juego completo de toallas de baño.',
            'price' => 0.00,
        ]);
        Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Limpieza de Habitación',
            'description' => 'Servicio completo de limpieza ahora.',
            'price' => 20.00,
        ]);

        Service::create([
            'category_id' => $mantenimiento->id,
            'name' => 'Reparación Aire Acondicionado',
            'description' => 'Solicitar técnico para revisión.',
            'price' => 0.00,
        ]);
        Service::create([
            'category_id' => $mantenimiento->id,
            'name' => 'Bombilla Fundida',
            'description' => 'Solicitar cambio de luces.',
            'price' => 0.00,
        ]);

        // --- 3. HABITACIONES
        Room::create(['number' => '101']);
        Room::create(['number' => '102']);
        Room::create(['number' => '103']);
        Room::create(['number' => '201']);
        Room::create(['number' => '202']);
    }
}
