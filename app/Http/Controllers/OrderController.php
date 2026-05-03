<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Habitacion;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules());

        $order = $this->createOrderFromValidatedData($validated);

        return redirect()->back()->with('success', 'Solicitud enviada correctamente');
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate($this->storeRules());

        $order = $this->createOrderFromValidatedData($validated);

        return response()->json([
            'message' => 'Solicitud enviada correctamente',
            'order' => $order->load(['services', 'habitacion']),
        ]);
    }

    public function statusApi(Order $order)
    {
        return response()->json([
            'order' => $order->load(['services', 'habitacion']),
        ]);
    }

    public function myOrders(Request $request)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|exists:habitacions,numero',
            'session_token' => 'required|string',
        ]);

        $habitacion = Habitacion::query()
            ->where('numero', $validated['room_number'])
            ->firstOrFail();

        if ($habitacion->status !== 'ocupada' || $habitacion->current_session_token !== $validated['session_token']) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesion invalida. Escanea de nuevo el QR de tu habitacion.',
            ]);
        }

        return response()->json([
            'orders' => Order::query()
                ->with(['services', 'habitacion'])
                ->where('habitacion_id', $habitacion->id)
                ->where('session_token', $validated['session_token'])
                ->latest()
                ->get(),
        ]);
    }

    public function tracking(Order $order)
    {
        return Inertia::render('Tracking', [
            'order' => $order->load(['services', 'habitacion']),
        ]);
    }

    private function storeRules(): array
    {
        return [
            'room_number' => 'required|string|exists:habitacions,numero',
            'habitacion_id' => 'nullable|integer|exists:habitacions,id',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'session_token' => 'nullable|string',
            'cart' => 'nullable|array',
            'cart.*.id' => 'required_with:cart|integer|exists:services,id',
            'cart.*.quantity' => 'required_with:cart|integer|min:1',
            'cart.*.price' => 'required_with:cart|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'requested_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:2000',
        ];
    }

    private function createOrderFromValidatedData(array $validated): Order
    {
        if ($validated['service_type'] === 'comida' && empty($validated['cart'])) {
            throw ValidationException::withMessages([
                'cart' => 'Debes añadir al menos un producto para pedir comida.',
            ]);
        }

        if ($validated['service_type'] === 'limpieza' && empty($validated['requested_time'])) {
            throw ValidationException::withMessages([
                'requested_time' => 'Debes seleccionar una hora para limpieza.',
            ]);
        }

        if ($validated['service_type'] === 'mantenimiento' && empty($validated['description'])) {
            throw ValidationException::withMessages([
                'description' => 'Debes describir la avería para mantenimiento.',
            ]);
        }

        return DB::transaction(function () use ($validated) {
            $habitacion = !empty($validated['habitacion_id'])
                ? Habitacion::query()->findOrFail($validated['habitacion_id'])
                : Habitacion::query()->where('numero', $validated['room_number'])->firstOrFail();

            if ($habitacion->status !== 'ocupada') {
                throw ValidationException::withMessages([
                    'room_number' => 'La habitacion no esta activa para solicitudes. Realiza check-in en recepcion.',
                ]);
            }

            if (($validated['session_token'] ?? null) !== $habitacion->current_session_token) {
                throw ValidationException::withMessages([
                    'session_token' => 'Sesion invalida. Escanea de nuevo el QR de tu habitacion.',
                ]);
            }

            $order = Order::query()->create([
                'habitacion_id' => $habitacion->id,
                'room_number' => $habitacion->numero,
                'session_token' => $habitacion->current_session_token,
                'guest_email' => $habitacion->guest_email,
                'service_type' => $validated['service_type'],
                'requested_time' => $validated['service_type'] === 'limpieza' ? $validated['requested_time'] : null,
                'description' => $validated['service_type'] === 'mantenimiento' ? $validated['description'] : null,
                'total_price' => $validated['service_type'] === 'comida' ? ($validated['total'] ?? 0) : 0,
                'status' => 'recibido',
            ]);

            if ($validated['service_type'] === 'comida') {
                foreach ($validated['cart'] as $item) {
                    $order->services()->attach($item['id'], [
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            }

            return $order;
        });
    }

    //--PARA ADMIN--
    public function index()
    {
        $orders = \App\Models\Order::with(['services', 'habitacion'])->latest()->get();

        return \Inertia\Inertia::render('Admin/Orders', [
            'orders' => $orders
        ]);
    }

    public function poll()
    {
        $orders = \App\Models\Order::with(['services', 'habitacion'])->latest()->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    // CAMBIAR ESTADO (SERVIR / PENDIENTE)
    public function update(\App\Models\Order $order, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:recibido,en_proceso,en_camino,completado',
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->back();
    }

}
