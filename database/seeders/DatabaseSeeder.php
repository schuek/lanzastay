<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Service;
use App\Models\Room;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- 0. USUARIOS (Para que nunca se borre tu acceso) ---
        User::create([
            'name' => 'Admin LanzaStay',
            'email' => 'admin@lanzastay.com',
            'password' => bcrypt('12345678'), // Tu contraseña será 12345678
        ]);

        // --- 1. CREAMOS LAS TRES GRANDES ÁREAS ---
        $restaurante = Category::create(['name' => 'Restaurante', 'icon' => 'CakeIcon']);
        $limpieza = Category::create(['name' => 'Limpieza', 'icon' => 'SparklesIcon']);
        $mantenimiento = Category::create(['name' => 'Mantenimiento', 'icon' => 'WrenchScrewdriverIcon']);

        // --- 2. SERVICIOS ---
        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Hamburguesa LanzaStay',
            'description' => 'Completa con queso y bacon.',
            'price' => 12.50,
            'service_type' => 'comida',
            'service_category' => 'Comida',
            'ingredients' => ['Pan brioche', 'Carne', 'Queso', 'Bacon', 'Lechuga', 'Tomate'],
            'is_vegan' => false,
        ]);
        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Mojito Cubano',
            'description' => 'Ron, lima y hierbabuena.',
            'price' => 7.50,
            'service_type' => 'comida',
            'service_category' => 'Bebida',
            'ingredients' => ['Ron', 'Lima', 'Hierbabuena', 'Azúcar', 'Soda'],
            'is_vegan' => true,
        ]);
        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Tarta de Queso',
            'description' => 'Porción casera con frutos rojos.',
            'price' => 5.50,
            'service_type' => 'comida',
            'service_category' => 'Postre',
            'ingredients' => ['Queso crema', 'Galleta', 'Mantequilla', 'Frutos rojos'],
            'is_vegan' => false,
        ]);

        Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Pack Toallas Extra',
            'description' => 'Juego completo de toallas de baño.',
            'price' => 0.00,
            'service_type' => 'limpieza',
            'service_category' => 'Limpieza',
            'ingredients' => [],
            'is_vegan' => false,
        ]);
        Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Limpieza de Habitación',
            'description' => 'Servicio completo de limpieza ahora.',
            'price' => 20.00,
            'service_type' => 'limpieza',
            'service_category' => 'Limpieza',
            'ingredients' => [],
            'is_vegan' => false,
        ]);

        Service::create([
            'category_id' => $mantenimiento->id,
            'name' => 'Reparación Aire Acondicionado',
            'description' => 'Solicitar técnico para revisión.',
            'price' => 0.00,
            'service_type' => 'mantenimiento',
            'service_category' => 'Mantenimiento',
            'ingredients' => [],
            'is_vegan' => false,
        ]);
        Service::create([
            'category_id' => $mantenimiento->id,
            'name' => 'Bombilla Fundida',
            'description' => 'Solicitar cambio de luces.',
            'price' => 0.00,
            'service_type' => 'mantenimiento',
            'service_category' => 'Mantenimiento',
            'ingredients' => [],
            'is_vegan' => false,
        ]);

        // --- 3. HABITACIONES ---
        Room::create(['numero' => '101']);
        Room::create(['numero' => '102']);
        Room::create(['numero' => '103']);
        Room::create(['numero' => '201']);
        Room::create(['numero' => '202']);

        // --- 4. EXCURSIONES ---
        $this->call([
            ActivitySeeder::class,
        ]);
    }
}
