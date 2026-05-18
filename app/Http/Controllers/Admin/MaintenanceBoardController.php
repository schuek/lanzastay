<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMaintenanceTicketRequest;
use App\Http\Requests\Admin\UpdateMaintenanceTicketRequest;
use App\Models\Habitacion;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceBoardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewMaintenanceTasks', Order::class);

        $orders = Order::with('habitacion')
            ->where('service_type', 'mantenimiento')
            ->latest()
            ->take(200)
            ->get();

        $rooms = Habitacion::query()
            ->where('activa', true)
            ->orderBy('numero')
            ->get(['id', 'numero']);

        return Inertia::render('Admin/MaintenanceBoard', [
            'orders' => $orders,
            'rooms' => $rooms,
        ]);
    }

    public function store(StoreMaintenanceTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $habitacion = Habitacion::query()->findOrFail($validated['habitacion_id']);

        Order::query()->create([
            'habitacion_id' => $habitacion->id,
            'room_number' => $habitacion->numero,
            'service_type' => 'mantenimiento',
            'description' => $validated['description'],
            'notas_internas' => $validated['notas_internas'] ?? null,
            'prioridad' => $validated['prioridad'] ?? 'media',
            'status' => 'recibido',
            'total_price' => 0,
        ]);

        return redirect()->back()->with('success', 'Ticket de mantenimiento creado.');
    }

    public function update(UpdateMaintenanceTicketRequest $request, Order $order): RedirectResponse
    {
        if ($order->service_type !== 'mantenimiento') {
            abort(404);
        }

        $validated = $request->validated();

        $payload = collect($validated)
            ->only(['status', 'prioridad', 'description', 'notas_internas', 'notas_resolucion'])
            ->filter(fn ($value) => $value !== null)
            ->all();

        if ($payload !== []) {
            $order->update($payload);
        }

        return redirect()->back();
    }
}
