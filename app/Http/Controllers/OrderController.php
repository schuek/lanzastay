<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Habitacion;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\OrderCreated;
use Inertia\Inertia;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $order = Order::query()->create([
            'room_number' => $request->input('room_number'),
            'service_type' => $request->input('service_type', 'comida'),
            'total_price' => $request->input('total', 0),
            'status' => 'recibido',
        ]);

        if ($request->has('cart')) {
            foreach ((array) $request->input('cart', []) as $item) {
                if (!isset($item['id'])) {
                    continue;
                }

                $order->services()->attach($item['id'], [
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                ]);
            }
        }

        $order->load(['services', 'habitacion']);

        broadcast(new OrderCreated($order));

        return response()->json([
            'order' => $order,
            'success' => true,
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

    public function checkout(Request $request, string $numero)
    {
        $validated = $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|integer',
            'cart.*.name' => 'required|string',
            'cart.*.price' => 'required|numeric|min:0',
            'cart.*.quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
            'session_token' => 'nullable|string',
        ]);

        $habitacion = Habitacion::query()->where('numero', $numero)->firstOrFail();
        if ($habitacion->status !== 'ocupada') {
            throw ValidationException::withMessages([
                'room_number' => 'La habitacion no esta activa para pagos.',
            ]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = collect($validated['cart'])->map(function (array $item): array {
            return [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                    'unit_amount' => (int) round(((float) $item['price']) * 100),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        })->values()->all();

        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => route('orders.checkout.success', ['numero' => $numero]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('orders.checkout.cancel', ['numero' => $numero]),
            'metadata' => [
                'room_number' => $numero,
            ],
        ]);

        session()->put('stripe_checkout_'.$session->id, [
            'room_number' => $numero,
            'habitacion_id' => $habitacion->id,
            'session_token' => $validated['session_token'] ?? $habitacion->current_session_token,
            'guest_email' => $habitacion->guest_email,
            'cart' => $validated['cart'],
            'total' => $validated['total'],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function checkoutSuccess(Request $request, string $numero)
    {
        $checkoutSessionId = (string) $request->query('session_id', '');
        if ($checkoutSessionId === '') {
            return redirect()->route('menu.show', ['numero' => $numero])->with('error', 'No se pudo verificar el pago.');
        }

        $payloadKey = 'stripe_checkout_'.$checkoutSessionId;
        $payload = session($payloadKey);
        if (! is_array($payload)) {
            return redirect()->route('menu.show', ['numero' => $numero])->with('error', 'No se encontró la sesión de pago.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $stripeSession = StripeCheckoutSession::retrieve($checkoutSessionId);
        } catch (\Throwable $e) {
            Log::warning('Stripe session retrieve failed', ['session_id' => $checkoutSessionId, 'error' => $e->getMessage()]);
            return redirect()->route('menu.show', ['numero' => $numero])->with('error', 'No se pudo validar el pago.');
        }

        if (($stripeSession->payment_status ?? null) !== 'paid') {
            return redirect()->route('menu.show', ['numero' => $numero])->with('error', 'El pago no se completó.');
        }

        $order = Order::query()->create([
            'habitacion_id' => $payload['habitacion_id'] ?? null,
            'room_number' => $payload['room_number'] ?? $numero,
            'session_token' => $payload['session_token'] ?? null,
            'guest_email' => $payload['guest_email'] ?? null,
            'service_type' => 'comida',
            'total_price' => $payload['total'] ?? 0,
            'status' => 'pagado',
        ]);

        foreach (($payload['cart'] ?? []) as $item) {
            if (! isset($item['id'])) {
                continue;
            }
            $order->services()->attach($item['id'], [
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
            ]);
        }

        session()->forget($payloadKey);

        return redirect()->route('menu.show', ['numero' => $numero])->with('success', 'Pago realizado correctamente. Pedido registrado.');
    }

    public function checkoutCancel(string $numero)
    {
        return redirect()->route('menu.show', ['numero' => $numero])->with('error', 'Pago cancelado. No se realizó ningún cargo.');
    }

    private function storeRules(): array
    {
        return [
            'room_number' => 'required|string|exists:habitacions,numero',
            'habitacion_id' => 'nullable|integer|exists:habitacions,id',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'session_token' => 'required|string',
            'cart' => 'nullable|array|min:1',
            'cart.*.id' => 'required_with:cart|integer|exists:services,id',
            'cart.*.quantity' => 'required_with:cart|integer|min:1',
            'cart.*.price' => 'required_with:cart|numeric|min:0',
            'total' => 'required_if:service_type,comida|numeric|min:0',
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
