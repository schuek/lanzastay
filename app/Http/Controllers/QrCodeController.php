<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class QrCodeController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-reception-operations');

        $habitaciones = Habitacion::query()
            ->select(['id', 'numero', 'access_token'])
            ->orderBy('numero')
            ->get()
            ->map(static fn (Habitacion $habitacion) => [
                'id' => $habitacion->id,
                'numero' => $habitacion->numero,
                'access_token' => $habitacion->access_token,
                'menu_url' => route('menu.show', $habitacion),
            ]);

        return Inertia::render('Admin/QrCodes', [
            'habitaciones' => $habitaciones,
        ]);
    }
}
