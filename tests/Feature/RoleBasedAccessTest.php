<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cocina_can_access_catalog(): void
    {
        $user = User::factory()->role(UserRole::COCINA)->create();

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertOk();
    }

    public function test_recepcion_cannot_access_catalog(): void
    {
        $user = User::factory()->role(UserRole::RECEPCION)->create();

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_limpieza_cannot_access_kitchen_orders(): void
    {
        $user = User::factory()->role(UserRole::LIMPIEZA)->create();

        $this->actingAs($user)
            ->get(route('orders.kitchen'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_mantenimiento_cannot_access_rooms(): void
    {
        $user = User::factory()->role(UserRole::MANTENIMIENTO)->create();

        $this->actingAs($user)
            ->get(route('rooms.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_access_catalog(): void
    {
        $user = User::factory()->role(UserRole::ADMIN)->create();

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertOk();
    }

    public function test_cocina_catalog_is_limited_to_restaurant_services(): void
    {
        $restaurante = Category::create(['name' => 'Restaurante', 'icon' => 'CakeIcon']);
        $limpieza = Category::create(['name' => 'Limpieza', 'icon' => 'SparklesIcon']);

        Service::create([
            'category_id' => $restaurante->id,
            'name' => 'Plato test',
            'price' => 10,
            'service_type' => 'comida',
            'service_category' => 'Comida',
        ]);
        Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Limpieza test',
            'price' => 0,
            'service_type' => 'limpieza',
            'service_category' => 'Limpieza',
        ]);

        $user = User::factory()->role(UserRole::COCINA)->create();

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Index')
                ->has('services', 1)
                ->where('services.0.service_type', 'comida')
                ->has('categories', 1)
                ->where('categories.0.name', 'Restaurante'));
    }

    public function test_cocina_cannot_edit_non_restaurant_service(): void
    {
        $limpieza = Category::create(['name' => 'Limpieza', 'icon' => 'SparklesIcon']);
        $service = Service::create([
            'category_id' => $limpieza->id,
            'name' => 'Limpieza test',
            'price' => 0,
            'service_type' => 'limpieza',
            'service_category' => 'Limpieza',
        ]);

        $user = User::factory()->role(UserRole::COCINA)->create();

        $this->actingAs($user)
            ->get(route('catalog.edit', $service))
            ->assertForbidden();
    }

    public function test_cocina_can_access_kitchen_orders(): void
    {
        $user = User::factory()->role(UserRole::COCINA)->create();

        $this->actingAs($user)
            ->get(route('orders.kitchen'))
            ->assertOk();
    }
}
