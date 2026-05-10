<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function index(): Response
    {
        $habitaciones = Habitacion::query()
            ->select(['id', 'numero'])
            ->orderBy('numero')
            ->get();

        return Inertia::render('Admin/QrCodes', [
            'habitaciones' => $habitaciones,
        ]);
    }
}
