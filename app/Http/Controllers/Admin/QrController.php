<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Habitacion;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    public function index()
    {
        $habitaciones = Habitacion::all()->map(function (Habitacion $habitacion) {
            return [
                'id' => $habitacion->id,
                'numero' => $habitacion->numero,
                'qr_svg' => (string) QrCode::format('svg')->size(220)->margin(1)->generate($habitacion->generateQrUrl()),
            ];
        })->values();

        return Inertia::render('Admin/QrCodes', [
            'habitaciones' => $habitaciones,
        ]);
    }
}
