<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Inertia\Inertia;
use App\Models\Category;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Habitacion;
use App\Models\Order;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Support\UserRole;

class ServiceController extends Controller
{
    public function welcomeGuest(Request $request, Habitacion $habitacion)
    {
        $token = $request->query('token');

        if ($habitacion->status !== 'ocupada') {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Bienvenido a LanzaStay. Por favor, realice su check-in en recepción para empezar a usar nuestros servicios.',
            ]);
        }

        if ($token !== null && $token !== '' && $token !== $habitacion->current_session_token) {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Sesion no valida para esta habitacion. Solicita un nuevo acceso en recepcion.',
            ]);
        }

        $sessionToken = $habitacion->current_session_token ?? '';
        if ($sessionToken === '') {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Sesion no valida para esta habitacion. Solicita un nuevo acceso en recepcion.',
            ]);
        }

        return Inertia::render('WelcomeGuest', [
            'roomNumber' => $habitacion->numero,
            'roomAccessToken' => $habitacion->access_token,
            'sessionToken' => $sessionToken,
            'guestEmail' => $habitacion->guest_email,
        ]);
    }

    public function registerGuest(Request $request)
    {
        $validated = $request->validate([
            'access_token' => 'required|uuid|exists:habitacions,access_token',
            'session_token' => 'required|string',
            'guest_email' => 'required|email:rfc,dns|max:255',
        ]);

        $room = Habitacion::query()->where('access_token', $validated['access_token'])->firstOrFail();

        if ($room->status !== 'ocupada' || $room->current_session_token !== $validated['session_token']) {
            return Inertia::render('ClientAccessDenied', [
                'message' => 'Sesion no valida para esta habitacion. Solicita un nuevo acceso en recepcion.',
            ]);
        }

        $room->update([
            'guest_email' => strtolower($validated['guest_email']),
        ]);

        return redirect()->route('menu.show', $room);
    }

    public function admin()
    {
        Gate::authorize('manage-catalog');

        return Inertia::render('Admin/Index', [
            'services' => $this->catalogServicesQuery()->get(),
        ]);
    }

    //2.eliminar UN servicio
    public function destroy(Service $service)
    {
        Gate::authorize('manage-catalog');
        $this->assertKitchenCanAccessService($service);

        $service->delete();
        return redirect()->back();
    }

    //mostrar formulario vacio
    public function create()
    {
        Gate::authorize('manage-catalog');

        return Inertia::render('Admin/Create', [
            'categories' => $this->catalogCategoriesForUser(),
        ]);
    }

    //mostrar detalles
    public function show(Service $service)
    {
        Gate::authorize('manage-catalog');
        $this->assertKitchenCanAccessService($service);

        $service->load('category');
        return Inertia::render('Admin/Show', [
            'service' => $service
        ]);
    }

    //editar un servicio
    public function edit(Service $service)
    {
        Gate::authorize('manage-catalog');
        $this->assertKitchenCanAccessService($service);

        return Inertia::render('Admin/Edit', [
            'service' => $service,
            'categories' => $this->catalogCategoriesForUser(),
        ]);
    }

    //Guardar nuevo servicio
    public function store(Request $request)
    {
        Gate::authorize('manage-catalog');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|numeric',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'service_category' => 'nullable|string|in:Comida,Bebida,Postre,Entrante,Limpieza,Mantenimiento',
            'categoria_restaurante' => 'nullable|string|in:Comida,Bebida,Postre,Entrante',
            'horario' => 'nullable|string|in:Desayuno,Almuerzo,Cena,Todo el dia',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'string|max:120',
            'is_vegan' => 'nullable|boolean',
            'image_url' => 'nullable|url|max:2048',
        ]);

        Service::create($this->normalizeServicePayload($this->applyKitchenCatalogConstraints($validated)));
        return to_route('catalog.index');
    }

    public function update(Request $request, Service $service)
    {
        Gate::authorize('manage-catalog');
        $this->assertKitchenCanAccessService($service);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|numeric',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'service_category' => 'nullable|string|in:Comida,Bebida,Postre,Entrante,Limpieza,Mantenimiento',
            'categoria_restaurante' => 'nullable|string|in:Comida,Bebida,Postre,Entrante',
            'horario' => 'nullable|string|in:Desayuno,Almuerzo,Cena,Todo el dia',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'string|max:120',
            'is_vegan' => 'nullable|boolean',
            'image_url' => 'nullable|url|max:2048',
        ]);

        $service->update($this->normalizeServicePayload($this->applyKitchenCatalogConstraints($validated)));

        return to_route('catalog.index');
    }

    private function normalizeServicePayload(array $validated): array
    {
        $serviceCategory = $validated['service_category'] ?? null;

        if (!$serviceCategory) {
            $serviceCategory = match ($validated['service_type']) {
                'limpieza' => 'Limpieza',
                'mantenimiento' => 'Mantenimiento',
                default => 'Comida',
            };
        }

        $isRestaurantService = $validated['service_type'] === 'comida';
        $restaurantCategory = $isRestaurantService
            ? ($validated['categoria_restaurante'] ?? (in_array($serviceCategory, ['Bebida', 'Postre', 'Entrante'], true) ? $serviceCategory : 'Comida'))
            : null;
        $schedule = $isRestaurantService
            ? ($validated['horario'] ?? 'Todo el dia')
            : null;

        return [
            ...$validated,
            'service_category' => $serviceCategory,
            'categoria_restaurante' => $restaurantCategory,
            'horario' => $schedule,
            'ingredients' => array_values(array_filter($validated['ingredients'] ?? [])),
            'is_vegan' => (bool) ($validated['is_vegan'] ?? false),
        ];
    }

    private function isKitchenCatalogRole(): bool
    {
        return auth()->user()?->role === UserRole::COCINA;
    }

    private function catalogServicesQuery()
    {
        return Service::with('category')
            ->where('service_type', 'comida')
            ->latest();
    }

    private function catalogCategoriesForUser()
    {
        $query = Category::query()->orderBy('name');

        if ($this->isKitchenCatalogRole()) {
            $query->where('name', 'Restaurante');
        }

        return $query->get();
    }

    private function assertKitchenCanAccessService(Service $service): void
    {
        if ($this->isKitchenCatalogRole() && $service->service_type !== 'comida') {
            abort(403);
        }
    }

    private function applyKitchenCatalogConstraints(array $validated): array
    {
        if (! $this->isKitchenCatalogRole()) {
            return $validated;
        }

        $restaurantCategoryId = Category::query()
            ->where('name', 'Restaurante')
            ->value('id');

        $validated['service_type'] = 'comida';
        if ($restaurantCategoryId) {
            $validated['category_id'] = $restaurantCategoryId;
        }

        return $validated;
    }

    public function qrcodes()
{
    $rooms = Habitacion::query()->where('activa', true)->orderBy('numero')->get();

    $codes = [];

    foreach ($rooms as $room) {
        $url = route('menu.show', $room);
        $qr = QrCode::size(220)->margin(1)->generate($url);

        $codes[] = [
            'id' => $room->id,
            'room' => $room->numero,
            'access_token' => $room->access_token,
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
        Gate::authorize('manage-reception-operations');

        $rooms = Habitacion::query()
            ->orderBy('numero')
            ->get()
            ->map(static fn (Habitacion $room) => [
                'id' => $room->id,
                'numero' => $room->numero,
                'access_token' => $room->access_token,
                'status' => $room->status,
                'current_session_token' => $room->current_session_token,
                'guest_email' => $room->guest_email,
                'check_in_at' => $room->check_in_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Rooms', [
            'rooms' => $rooms,
        ]);
    }
    // GUARDAR NUEVA HABITACIÓN
    public function storeRoom(Request $request)
    {
        Gate::authorize('manage-reception-operations');

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
        Gate::authorize('manage-reception-operations');

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
            'guest_email' => $request->status === 'ocupada' ? $room->guest_email : null,
        ]);

        return redirect()->back();
    }

    // BORRAR HABITACIÓN
    public function destroyRoom(Habitacion $room)
    {
        Gate::authorize('manage-reception-operations');

        $room->delete();
        return redirect()->back();
    }

    public function checkInRoom(Habitacion $room)
    {
        Gate::authorize('manage-reception-operations');

        $validated = request()->validate([
            'guest_email' => 'required|email:rfc,dns|max:255',
        ]);

        $room->update([
            'status' => 'ocupada',
            'current_session_token' => Str::random(40),
            'guest_email' => strtolower($validated['guest_email']),
            'check_in_at' => now(),
        ]);

        return redirect()->back();
    }

    public function checkOutRoom(Habitacion $room)
    {
        Gate::authorize('manage-reception-operations');

        return App::make(StayCheckoutController::class)->processRoomCheckout($room);
    }
}
