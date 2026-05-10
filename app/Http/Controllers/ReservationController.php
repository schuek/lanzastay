<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
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
}
