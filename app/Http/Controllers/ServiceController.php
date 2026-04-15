<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Inertia\Inertia;
use App\Models\Category;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\Activity;
use App\Models\ActivityReservation;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
public function index(Request $request)
    {
        $services = Service::with('category')->get();
        $categories = Category::all();

        $roomNumber = $request->query('habitacion', $request->query('room', '101'));
        $token = $request->query('token');
        $room = Habitacion::query()->where('numero', $roomNumber)->first();

        if (!$room || $room->status !== 'ocupada') {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Bienvenido a LanzaStay. Por favor, realice su check-in en recepción para empezar a usar nuestros servicios',
            ]);
        }

        if (!$token || $token !== $room->current_session_token) {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Sesion no valida para esta habitacion. Solicita un nuevo acceso en recepcion.',
            ]);
        }

        $myOrders = \App\Models\Order::with('services')
                        ->whereHas('habitacion', function ($query) use ($roomNumber) {
                            $query->where('numero', $roomNumber);
                        })
                        ->orderBy('created_at', 'desc')
                        ->get();

        $activities = Activity::query()
            ->orderBy('date_time')
            ->get();

        $myReservations = ActivityReservation::query()
            ->with('activity')
            ->where('room_id', $room->id)
            ->latest()
            ->get();

        return Inertia::render('Menu', [
            'services' => $services,
            'categories' => $categories,
            'myOrders' => $myOrders,
            'activities' => $activities,
            'myReservations' => $myReservations,
            'currentRoom' => $roomNumber,
            'currentRoomId' => $room?->id,
            'sessionToken' => $token,
        ]);
    }

public function admin()
    {
        $services = Service::with('category')->latest()->get();

        $categories = Category::all();

        return Inertia::render('Admin/Index', [
            'services' => $services,
            'categories' => $categories
        ]);
    }

    //2.eliminar UN servicio
    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->back();
    }

    //mostrar formulario vacio
    public function create()
    {
        return Inertia::render('Admin/Create', [
            'categories' => Category::all() // Necesitamos las categorías para el desplegable
        ]);
    }

    //mostrar detalles
    public function show(Service $service)
    {
        $service->load('category');
        return Inertia::render('Admin/Show', [
            'service' => $service
        ]);
    }

    //editar un servicio
    public function edit(Service $service)
    {
        return Inertia::render('Admin/Edit', [
            'service' => $service,
            'categories' => Category::all()
        ]);
    }

    //Guardar nuevo servicio
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|numeric',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'image_url' => 'nullable|url|max:2048',
        ]);

        Service::create($validated);
        return to_route('admin.index');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|numeric',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'image_url' => 'nullable|url|max:2048',
        ]);

        $service->update($validated);

        return to_route('admin.index');
    }

    public function qrcodes()
{
    $rooms = Habitacion::query()->where('activa', true)->orderBy('numero')->get();

    $codes = [];

    foreach ($rooms as $room) {
        $url = route('menu', [
            'habitacion' => $room->numero,
            'token' => $room->current_session_token,
        ]);
        $qr = QrCode::size(220)->margin(1)->generate($url);

        $codes[] = [
            'id' => $room->id,
            'room' => $room->numero,
            'status' => $room->status,
            'current_session_token' => $room->current_session_token,
            'menu_url' => $url,
            'qr' => (string) $qr,
        ];
    }

    return Inertia::render('Admin/QrCodes', [
        'codes' => $codes
    ]);


}

    public function rooms()
    {
        return Inertia::render('Admin/Rooms', [
            'rooms' => Habitacion::query()->orderBy('numero')->get(),
        ]);
    }
    // GUARDAR NUEVA HABITACIÓN
    public function storeRoom(Request $request)
    {
        $request->validate([
            'number' => 'required|string|unique:habitacions,numero|max:10',
            'status' => 'required|in:disponible,ocupada,mantenimiento',
        ]);

        $sessionToken = $request->status === 'ocupada' ? Str::random(40) : null;

        Habitacion::create([
            'numero' => $request->number,
            'activa' => true,
            'status' => $request->status,
            'current_session_token' => $sessionToken,
        ]);

        return redirect()->back();
    }

    public function updateRoom(Request $request, Habitacion $room)
    {
        $request->validate([
            'number' => 'required|string|max:10|unique:habitacions,numero,' . $room->id,
            'status' => 'required|in:disponible,ocupada,mantenimiento',
        ]);

        $token = $room->current_session_token;
        if ($request->status === 'ocupada' && !$token) {
            $token = Str::random(40);
        }
        if ($request->status !== 'ocupada') {
            $token = null;
        }

        $room->update([
            'numero' => $request->number,
            'status' => $request->status,
            'current_session_token' => $token,
        ]);

        return redirect()->back();
    }

    // BORRAR HABITACIÓN
    public function destroyRoom(Habitacion $room)
    {
        $room->delete();
        return redirect()->back();
    }

    public function checkInRoom(Habitacion $room)
    {
        $room->update([
            'status' => 'ocupada',
            'current_session_token' => Str::random(40),
        ]);

        return redirect()->back();
    }

    public function checkOutRoom(Habitacion $room)
    {
        Order::query()
            ->where('habitacion_id', $room->id)
            ->where('status', '!=', 'completado')
            ->update(['status' => 'completado']);

        $room->update([
            'status' => 'disponible',
            'current_session_token' => null,
        ]);

        return redirect()->back();
    }
}
