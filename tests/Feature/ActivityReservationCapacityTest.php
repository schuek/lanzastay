<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Models\Habitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityReservationCapacityTest extends TestCase
{
    use RefreshDatabase;

    private function reservationPayload(Habitacion $habitacion, Activity $activity, int $plazas): array
    {
        return [
            'access_token' => $habitacion->access_token,
            'session_token' => $habitacion->current_session_token,
            'activity_id' => $activity->id,
            'plazas' => $plazas,
            'scheduled_time' => '10:00',
            'payment_method' => 'efectivo',
        ];
    }

    public function test_reservation_is_always_pending(): void
    {
        $habitacion = $this->createRoom();
        $activity = $this->createLimitedActivity(10);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($habitacion, $activity, 2));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('activity_reservations', [
            'activity_id' => $activity->id,
            'seats_booked' => 2,
            'status' => 'pendiente',
            'scheduled_time' => '10:00',
            'payment_method' => 'efectivo',
        ]);
    }

    public function test_plazas_disponibles_decrease_with_pending_reservations(): void
    {
        $habitacion = $this->createRoom();
        $activity = $this->createLimitedActivity(5);

        $this->postJson('/api/reservations', $this->reservationPayload($habitacion, $activity, 2))
            ->assertOk();

        $activity->refresh();
        $this->assertSame(3, $activity->plazas_disponibles);
    }

    public function test_reservation_rejects_when_not_enough_plazas(): void
    {
        $habitacion = $this->createRoom();
        $activity = $this->createLimitedActivity(3);

        ActivityReservation::query()->create([
            'room_id' => $habitacion->id,
            'session_token' => $habitacion->current_session_token,
            'activity_id' => $activity->id,
            'seats_booked' => 2,
            'scheduled_time' => '09:00',
            'total_price' => 0,
            'status' => 'pendiente',
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($habitacion, $activity, 2));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'Lo sentimos, no quedan suficientes plazas para esta hora.',
            ]);
    }

    public function test_unlimited_capacity_activity_allows_any_plazas(): void
    {
        $habitacion = $this->createRoom();
        $activity = Activity::query()->create([
            'name' => 'Gimnasio',
            'description' => 'Acceso libre',
            'type' => 'hotel_activity',
            'date_time' => now()->addDay(),
            'price' => 0,
            'max_seats' => null,
        ]);

        $this->assertTrue($activity->acceso_libre);
        $this->assertNull($activity->plazas_disponibles);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($habitacion, $activity, 8));

        $response->assertOk()->assertJsonPath('reservation.status', 'pendiente');
    }

    private function createRoom(): Habitacion
    {
        return Habitacion::query()->create([
            'numero' => (string) random_int(100, 999),
            'activa' => true,
            'status' => 'ocupada',
            'current_session_token' => Str::random(40),
            'guest_email' => 'huesped@test.com',
        ]);
    }

    private function createLimitedActivity(int $maxSeats): Activity
    {
        return Activity::query()->create([
            'name' => 'Yoga test',
            'description' => 'Test',
            'type' => 'hotel_activity',
            'date_time' => now()->addDay(),
            'price' => 10,
            'max_seats' => $maxSeats,
        ]);
    }
}
