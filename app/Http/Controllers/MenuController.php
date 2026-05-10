<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function show(Request $request, string $numero): Response
    {
        $habitacion = Habitacion::query()
            ->where('numero', $numero)
            ->firstOrFail();

        if ($habitacion->status !== 'ocupada') {
            return Inertia::render('Menu/Error', [
                'message' => 'Habitación inactiva. Por favor, realice el check-in.',
            ]);
        }

        $sessionToken = (string) ($habitacion->current_session_token ?? '');
        if ($sessionToken === '') {
            return Inertia::render('Menu/Error', [
                'message' => 'Habitación inactiva. Por favor, realice el check-in.',
            ]);
        }

        if (empty($habitacion->guest_email)) {
            return redirect()->route('guest.welcome', [
                'habitacion' => $habitacion->numero,
            ]);
        }

        $previousRoom = $request->session()->get('menu_habitacion_numero');
        if (! $request->session()->has('menu_device_key') || (string) $previousRoom !== (string) $habitacion->numero) {
            $request->session()->regenerate();
            $request->session()->put('menu_device_key', (string) Str::uuid());
        }

        $request->session()->put('menu_habitacion_numero', $habitacion->numero);
        $request->session()->put('menu_habitacion_id', $habitacion->id);
        $request->session()->put('menu_session_token', $sessionToken);

        $myOrders = Order::query()
            ->where('room_number', $numero)
            ->with('services')
            ->latest()
            ->get();

        $myReservations = ActivityReservation::query()
            ->with('activity')
            ->where('room_id', $habitacion->id)
            ->where('session_token', $sessionToken)
            ->latest()
            ->get();

        return Inertia::render('Menu', [
            'services' => Service::query()->with('category')->get(),
            'myOrders' => $myOrders,
            'activities' => Activity::query()->orderBy('date_time')->get(),
            'myReservations' => $myReservations,
            'currentRoom' => (string) $habitacion->numero,
            'currentRoomId' => $habitacion->id,
            'sessionToken' => $sessionToken,
            'guestEmail' => $habitacion->guest_email,
        ]);
    }
}
