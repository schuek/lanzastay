<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Models\Habitacion;
use App\Models\ReservaActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function storeGuestReservation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_number' => ['required', 'string', 'exists:habitacions,numero'],
            'session_token' => ['required', 'string'],
            'actividad_id' => ['required', 'integer'],
            'titulo' => ['required', 'string', 'max:255'],
            'horario' => ['required', 'string', 'max:100'],
            'precio' => ['required', 'numeric', 'min:0'],
            'num_personas' => ['required', 'integer', 'min:1'],
            'plazas_disponibles' => ['required', 'integer', 'min:1'],
        ]);

        $room = Habitacion::query()->where('numero', $validated['room_number'])->firstOrFail();

        if ($room->status !== 'ocupada' || $room->current_session_token !== $validated['session_token']) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesion invalida para esta habitacion.',
            ]);
        }

        if (!$room->guest_email) {
            throw ValidationException::withMessages([
                'guest_email' => 'Debes registrar el email antes de reservar.',
            ]);
        }

        if ((int) $validated['num_personas'] > (int) $validated['plazas_disponibles']) {
            throw ValidationException::withMessages([
                'num_personas' => 'No hay plazas suficientes para completar la reserva.',
            ]);
        }

        $reservation = ReservaActividad::query()->create([
            'habitacion_id' => $room->id,
            'actividad_id' => $validated['actividad_id'],
            'email_cliente' => $room->guest_email,
            'titulo_actividad' => $validated['titulo'],
            'horario_actividad' => $validated['horario'],
            'num_personas' => $validated['num_personas'],
            'precio_total' => ((float) $validated['precio']) * ((int) $validated['num_personas']),
            'fecha' => now(),
        ]);

        return response()->json([
            'message' => 'Reserva confirmada correctamente.',
            'reservation' => $reservation,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_number' => ['required', 'string', 'exists:habitacions,numero'],
            'session_token' => ['required', 'string'],
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'seats_booked' => ['required', 'integer', 'min:1'],
        ]);

        $reservation = DB::transaction(function () use ($validated) {
            $room = Habitacion::query()->where('numero', $validated['room_number'])->firstOrFail();

            if ($room->status !== 'ocupada') {
                throw ValidationException::withMessages([
                    'room_number' => 'La habitacion no esta activa para reservar.',
                ]);
            }

            if ($room->current_session_token !== $validated['session_token']) {
                throw ValidationException::withMessages([
                    'session_token' => 'Sesion invalida para esta habitacion.',
                ]);
            }

            $activity = Activity::query()->lockForUpdate()->findOrFail($validated['activity_id']);
            $alreadyBookedSeats = ActivityReservation::query()
                ->where('activity_id', $activity->id)
                ->where('status', '!=', 'cancelada')
                ->sum('seats_booked');

            $availableSeats = (int) $activity->max_seats - (int) $alreadyBookedSeats;
            if ((int) $validated['seats_booked'] > $availableSeats) {
                throw ValidationException::withMessages([
                    'seats_booked' => 'No hay plazas suficientes. Solo quedan '.$availableSeats.'.',
                ]);
            }

            return ActivityReservation::query()->create([
                'room_id' => $room->id,
                'session_token' => $validated['session_token'],
                'activity_id' => $activity->id,
                'seats_booked' => $validated['seats_booked'],
                'total_price' => $activity->price * $validated['seats_booked'],
                'status' => 'pendiente',
            ]);
        });

        return response()->json([
            'message' => 'Reserva enviada correctamente.',
            'reservation' => $reservation->load(['activity', 'room']),
        ]);
    }

    public function indexAdmin(): Response
    {
        return Inertia::render('Admin/ActivitiesReservations', [
            'activities' => Activity::query()->latest('date_time')->get(),
            'reservations' => ActivityReservation::query()
                ->with(['activity', 'room'])
                ->latest()
                ->get(),
            'activeTab' => 'reception',
        ]);
    }

    public function updateStatus(ActivityReservation $reservation, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pendiente,confirmada,cancelada'],
        ]);

        $reservation->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back();
    }

    public function myReservations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_number' => ['required', 'string', 'exists:habitacions,numero'],
            'session_token' => ['required', 'string'],
        ]);

        $room = Habitacion::query()->where('numero', $validated['room_number'])->firstOrFail();
        if ($room->current_session_token !== $validated['session_token']) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesion invalida para esta habitacion.',
            ]);
        }

        $reservations = ActivityReservation::query()
            ->with(['activity'])
            ->where('room_id', $room->id)
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations,
        ]);
    }
}
