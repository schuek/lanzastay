<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Inertia\Inertia;
use App\Models\Category;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Room;

class ServiceController extends Controller
{
    public function index()
    {

        $services = Service::with('category')->get();

        $categories = Category::all();

        return Inertia::render('Menu', [
            'services' => $services,
            'categories' => $categories
        ]);
    }

    //1. panel de admionistracion
public function admin()
    {
        $services = Service::with('category')->get();

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
        ]);

        $service = Service::create($validated);
        return to_route('admin.index');
    }

    public function qrcodes()
{
    $rooms = Room::all();

    $codes = [];

    foreach ($rooms as $room) {
        $url = route('menu', ['room' => $room->number]);
        $qr = QrCode::size(200)->generate($url);

        $codes[] = [
            'room' => $room->number,
            'qr' => (string) $qr
        ];
    }

    return Inertia::render('Admin/QrCodes', [
        'codes' => $codes
    ]);


}
    // GUARDAR NUEVA HABITACIÓN
    public function storeRoom(Request $request)
    {
        $request->validate([
            'number' => 'required|string|unique:rooms,number|max:10'
        ]);

        Room::create([
            'number' => $request->number
        ]);

        return redirect()->back();
    }

    // BORRAR HABITACIÓN
    public function destroyRoom(Room $room)
    {
        $room->delete();
        return redirect()->back();
    }
}
