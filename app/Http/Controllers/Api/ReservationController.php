<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Models\Habitacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Creación tolerante a distintos nombres de campo en el cliente (activity_id / actividad_id, etc.).
     * Persistencia en activity_reservations: columnas reales room_id, session_token, activity_id, seats_booked, total_price, status.
     */
    public function store(Request $request): JsonResponse
    {
        $activityId = $request->input('activity_id') ?? $request->input('actividad_id');

        $guests = $request->input('seats_booked')
            ?? $request->input('num_personas')
            ?? $request->input('guests')
            ?? 1;
        $guests = max(1, (int) $guests);

        $room = null;
        if ($request->filled('room_number')) {
            $room = Habitacion::query()->where('numero', (string) $request->input('room_number'))->first();
        }
        if (! $room && $request->filled('habitacion_id')) {
            $room = Habitacion::query()->find((int) $request->input('habitacion_id'));
        }
        if (! $room && $request->filled('room_id')) {
            $room = Habitacion::query()->find((int) $request->input('room_id'));
        }

        if (! $room) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo identificar la habitacion.',
            ], 422);
        }

        if (! $activityId) {
            return response()->json([
                'success' => false,
                'message' => 'Falta el identificador de actividad.',
            ], 422);
        }

        $activity = Activity::query()->find((int) $activityId);
        if (! $activity) {
            return response()->json([
                'success' => false,
                'message' => 'Actividad no encontrada.',
            ], 422);
        }

        $sessionToken = (string) $request->input('session_token', '');
        if ($sessionToken === '') {
            $sessionToken = (string) ($room->current_session_token ?? '');
        }
        if (strlen($sessionToken) > 80) {
            $sessionToken = substr($sessionToken, 0, 80);
        }

        $reservation = ActivityReservation::query()->create([
            'room_id' => $room->id,
            'session_token' => $sessionToken,
            'activity_id' => $activity->id,
            'seats_booked' => $guests,
            'total_price' => round((float) $activity->price * $guests, 2),
            'status' => 'confirmada',
        ]);

        $reservation->load(['activity', 'room']);

        return response()->json([
            'success' => true,
            'reservation' => $reservation,
        ]);
    }
}
