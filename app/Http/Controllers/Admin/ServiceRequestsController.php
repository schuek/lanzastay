<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestsController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-reception-operations');

        return Inertia::render('Admin/ServiceRequests', [
            'peticionesLimpieza' => Order::query()
                ->with('habitacion')
                ->cleaningBoard()
                ->latest()
                ->get(),
            'avisosMantenimiento' => Order::query()
                ->with('habitacion')
                ->where('service_type', 'mantenimiento')
                ->latest()
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        if (! in_array($order->service_type, ['limpieza', 'mantenimiento'], true)) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:recibido,en_proceso,en_camino,completado',
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->back();
    }
}
