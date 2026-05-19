<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-reception-operations');

        $habitaciones = Habitacion::query()
            ->orderBy('numero')
            ->get()
            ->map(function (Habitacion $habitacion) {
                if (empty($habitacion->access_token)) {
                    $habitacion->forceFill([
                        'access_token' => (string) Str::uuid(),
                    ])->saveQuietly();
                }

                return [
                    'id' => $habitacion->id,
                    'numero' => $habitacion->numero,
                    'access_token' => $habitacion->access_token,
                    'menu_url' => $habitacion->generateQrUrl(),
                ];
            });

        return Inertia::render('Admin/QrCodes', [
            'habitaciones' => $habitaciones,
        ]);
    }
}
