<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Habitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityReservationCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_decrements_plazas_disponibles(): void
    {
        $habitacion = Habitacion::query()->create([
            'numero' => '901',
            'activa' => true,
            'status' => 'ocupada',
            'current_session_token' => 'session-test-token',
            'guest_email' => 'huesped@test.com',
        ]);

        $activity = Activity::query()->create([
            'name' => 'Spa test',
            'description' => 'Test',
            'type' => 'hotel_activity',
            'date_time' => now()->addDay(),
            'price' => 10,
            'max_seats' => 5,
            'plazas_disponibles' => 5,
        ]);

        $response = $this->postJson('/api/reservations', [
            'access_token' => $habitacion->access_token,
            'session_token' => $habitacion->current_session_token,
            'activity_id' => $activity->id,
            'plazas' => 2,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $activity->refresh();
        $this->assertSame(3, $activity->plazas_disponibles);
    }

    public function test_reservation_rejects_when_not_enough_plazas(): void
    {
        $habitacion = Habitacion::query()->create([
            'numero' => '902',
            'activa' => true,
            'status' => 'ocupada',
            'current_session_token' => Str::random(40),
            'guest_email' => 'huesped2@test.com',
        ]);

        $activity = Activity::query()->create([
            'name' => 'Tour test',
            'description' => 'Test',
            'type' => 'bus_tour',
            'date_time' => now()->addDay(),
            'price' => 20,
            'max_seats' => 3,
            'plazas_disponibles' => 1,
        ]);

        $response = $this->postJson('/api/reservations', [
            'access_token' => $habitacion->access_token,
            'session_token' => $habitacion->current_session_token,
            'activity_id' => $activity->id,
            'seats_booked' => 3,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);

        $activity->refresh();
        $this->assertSame(1, $activity->plazas_disponibles);
    }

    public function test_reservation_succeeds_when_plazas_column_is_zero_but_capacity_exists(): void
    {
        $habitacion = Habitacion::query()->create([
            'numero' => '903',
            'activa' => true,
            'status' => 'ocupada',
            'current_session_token' => Str::random(40),
            'guest_email' => 'huesped3@test.com',
        ]);

        $activity = Activity::query()->create([
            'name' => 'Legacy cupo',
            'description' => 'Test',
            'type' => 'hotel_activity',
            'date_time' => now()->addDay(),
            'price' => 15,
            'max_seats' => 4,
            'plazas_disponibles' => 0,
        ]);

        $response = $this->postJson('/api/reservations', [
            'access_token' => $habitacion->access_token,
            'session_token' => $habitacion->current_session_token,
            'activity_id' => $activity->id,
            'plazas' => 2,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $activity->refresh();
        $this->assertSame(2, $activity->plazas_disponibles);
    }
}
