<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Support\GuestRoomResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    /**
     * Crea una reserva de actividad para el huésped autenticado por access_token + session_token.
     *
     * Reglas de negocio:
     * - Error solo si plazas solicitadas > plazas_disponibles (columna en BD).
     * - Operación atómica: reserva + decremento de cupo en la misma transacción.
     */
    public function store(Request $request): JsonResponse
    {
        $activityId = $request->input('activity_id') ?? $request->input('actividad_id');

        if (! $activityId) {
            return response()->json([
                'success' => false,
                'message' => 'Falta el identificador de actividad.',
            ], 422);
        }

        try {
            $room = GuestRoomResolver::fromRequest($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'No se pudo identificar la habitacion.',
                'errors' => $e->errors(),
            ], 422);
        }

        $sessionToken = $this->resolveSessionToken($request, $room->current_session_token);

        try {
            $plazasSolicitadas = $this->resolvePlazasSolicitadas($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $reservation = DB::transaction(function () use ($activityId, $room, $sessionToken, $plazasSolicitadas) {
                /** @var Activity $activity */
                $activity = Activity::query()
                    ->lockForUpdate()
                    ->find((int) $activityId);

                if (! $activity) {
                    throw ValidationException::withMessages([
                        'activity_id' => 'Actividad no encontrada.',
                    ]);
                }

                $activity->sincronizarCupoDisponible();
                $activity->refresh();

                $plazasDisponibles = max(0, (int) $activity->plazas_disponibles);

                Log::info('Reserva actividad: comprobación de cupo', [
                    'activity_id' => $activity->id,
                    'plazas_solicitadas' => $plazasSolicitadas,
                    'plazas_disponibles' => $plazasDisponibles,
                    'max_seats' => (int) $activity->max_seats,
                    'room_id' => $room->id,
                ]);

                $this->assertPlazasDisponibles($activity, $plazasSolicitadas, $plazasDisponibles);

                $reservation = ActivityReservation::query()->create([
                    'room_id' => $room->id,
                    'session_token' => $sessionToken,
                    'activity_id' => $activity->id,
                    'seats_booked' => $plazasSolicitadas,
                    'total_price' => round((float) $activity->price * $plazasSolicitadas, 2),
                    'status' => 'confirmada',
                ]);

                $activity->decrementarCupo($plazasSolicitadas);

                Log::info('Reserva actividad: cupo actualizado', [
                    'activity_id' => $activity->id,
                    'plazas_restantes' => max(0, (int) $activity->fresh()->plazas_disponibles),
                    'reservation_id' => $reservation->id,
                ]);

                return $reservation;
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $reservation->load(['activity', 'room']);

        return response()->json([
            'success' => true,
            'reservation' => $reservation,
        ]);
    }

    /**
     * Acepta alias del cliente: plazas, seats_booked, guests, num_personas.
     */
    private function resolvePlazasSolicitadas(Request $request): int
    {
        $raw = $request->input('plazas')
            ?? $request->input('seats_booked')
            ?? $request->input('num_personas')
            ?? $request->input('guests')
            ?? 1;

        $plazas = (int) $raw;

        if ($plazas < 1) {
            throw ValidationException::withMessages([
                'plazas' => 'Debes reservar al menos 1 plaza.',
                'seats_booked' => 'Debes reservar al menos 1 plaza.',
            ]);
        }

        return $plazas;
    }

    private function resolveSessionToken(Request $request, ?string $expectedToken): string
    {
        $sessionToken = (string) $request->input('session_token', '');

        if ($sessionToken === '' || $sessionToken !== (string) $expectedToken) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesión inválida para esta habitación.',
            ]);
        }

        return strlen($sessionToken) > 80 ? substr($sessionToken, 0, 80) : $sessionToken;
    }

    /**
     * @throws ValidationException
     */
    private function assertPlazasDisponibles(Activity $activity, int $plazasSolicitadas, int $plazasDisponibles): void
    {
        if ($plazasSolicitadas > $plazasDisponibles) {
            $mensaje = $plazasDisponibles === 0
                ? 'No quedan plazas disponibles para esta actividad.'
                : "Solo quedan {$plazasDisponibles} plaza(s) disponible(s). Has solicitado {$plazasSolicitadas}.";

            throw ValidationException::withMessages([
                'plazas' => $mensaje,
                'seats_booked' => $mensaje,
            ]);
        }
    }
}
