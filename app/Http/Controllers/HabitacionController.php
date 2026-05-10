<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use Inertia\Inertia;
use Inertia\Response;

class HabitacionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rooms', [
            'rooms' => Habitacion::query()->orderBy('numero')->get(),
        ]);
    }
}
