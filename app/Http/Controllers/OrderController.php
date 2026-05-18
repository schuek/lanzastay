<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Habitacion;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\OrderCreated;
use App\Support\AmenityRequestType;
use App\Support\CleaningRequestType;
use App\Support\GuestRoomResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Stripe\Charge;
use Stripe\Exception\CardException as StripeCardException;
use Stripe\Stripe;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate(array_merge($this->storeRules(), [
                'notas' => 'nullable|string|max:2000',
                'stripe_token' => 'nullable|string',
            ]));

            $paidWithCard = ! empty($validated['stripe_token']);

            $order = $this->createOrderFromValidatedData($validated);
            $order->load(['services', 'habitacion']);

            if ($order->service_type === 'comida') {
                broadcast(new OrderCreated($order));
            }

            if ($paidWithCard) {
                return response()->json([
                    'message' => 'Pago completado y pedido en cocina',
                ], 200);
            }

            return response()->json([
                'message' => 'Pedido creado con éxito',
                'order' => $order,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'No se pudo validar el pedido.',
                'errors' => $e->errors(),
            ], 422);
        }
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
            'access_token' => 'required|uuid|exists:habitacions,access_token',
            'session_token' => 'required|string',
        ]);

        $habitacion = GuestRoomResolver::fromAccessToken(
            $validated['access_token'],
            $validated['session_token'],
        );

        return response()->json([
            'orders' => $habitacion->ordersForCurrentStay(),
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
            'access_token' => 'required|uuid|exists:habitacions,access_token',
            'service_type' => 'required|in:comida,limpieza,mantenimiento',
            'session_token' => 'required|string',
            'cart' => 'nullable|array|min:1',
            'cart.*.id' => 'required_with:cart|integer|exists:services,id',
            'cart.*.quantity' => 'required_with:cart|integer|min:1',
            'cart.*.price' => 'required_with:cart|numeric|min:0',
            'total' => 'required_if:service_type,comida|numeric|min:0',
            'requested_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:2000',
            'notas' => 'nullable|string|max:2000',
            'stripe_token' => 'nullable|string',
        ];
    }

    private function chargeWithStripeToken(string $tokenId, float $amountEur, string $description): void
    {
        if ($amountEur <= 0) {
            throw ValidationException::withMessages([
                'total' => 'El importe del pedido no es válido.',
            ]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            Charge::create([
                'amount' => (int) round($amountEur * 100),
                'currency' => 'eur',
                'source' => $tokenId,
                'description' => $description,
            ]);
        } catch (StripeCardException $e) {
            throw ValidationException::withMessages([
                'stripe_token' => $e->getError()->message ?? 'No se pudo procesar el pago con tarjeta.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe charge failed', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'stripe_token' => 'No se pudo procesar el pago. Inténtalo de nuevo.',
            ]);
        }
    }

    private function createOrderFromValidatedData(array $validated): Order
    {
        $description = $validated['description'] ?? null;

        if ($validated['service_type'] === 'comida') {
            $isRestaurantAmenity = AmenityRequestType::isRestaurantAmenity($description);

            if (AmenityRequestType::isHousekeepingAmenity($description)) {
                throw ValidationException::withMessages([
                    'description' => 'Las peticiones de limpieza deben enviarse como servicio de housekeeping.',
                ]);
            }

            if (! $isRestaurantAmenity && empty($validated['cart'])) {
                throw ValidationException::withMessages([
                    'cart' => 'Debes añadir al menos un producto para pedir comida.',
                ]);
            }

            if ($isRestaurantAmenity && ! in_array($description, AmenityRequestType::restaurantCodes(), true)) {
                throw ValidationException::withMessages([
                    'description' => 'Tipo de amenity no válido.',
                ]);
            }
        }

        if ($validated['service_type'] === 'limpieza') {
            if (AmenityRequestType::isRestaurantAmenity($description)) {
                throw ValidationException::withMessages([
                    'description' => 'Agua y bocadillo se envían a cocina, no a limpieza.',
                ]);
            }

            if ($description !== null && $description !== '' && ! CleaningRequestType::isAllowed($description)) {
                throw ValidationException::withMessages([
                    'description' => 'Tipo de solicitud de limpieza no válido.',
                ]);
            }

            $isHousekeepingAmenity = CleaningRequestType::isAmenity($description)
                || AmenityRequestType::isHousekeepingAmenity($description);

            if (! $isHousekeepingAmenity && empty($validated['requested_time'])) {
                throw ValidationException::withMessages([
                    'requested_time' => 'Debes seleccionar una hora para la limpieza de habitación.',
                ]);
            }
        }

        if ($validated['service_type'] === 'mantenimiento' && empty($validated['description'])) {
            throw ValidationException::withMessages([
                'description' => 'Debes describir la avería para mantenimiento.',
            ]);
        }

        return DB::transaction(function () use ($validated) {
            $habitacion = GuestRoomResolver::fromAccessToken(
                $validated['access_token'],
                $validated['session_token'],
            );

            $isRestaurantAmenity = $validated['service_type'] === 'comida'
                && AmenityRequestType::isRestaurantAmenity($description);

            $orderTotal = $validated['service_type'] === 'comida' && ! $isRestaurantAmenity
                ? (float) ($validated['total'] ?? 0)
                : 0.0;
            $paidWithCard = ! empty($validated['stripe_token']);

            if ($paidWithCard) {
                $this->chargeWithStripeToken(
                    $validated['stripe_token'],
                    $orderTotal,
                    'LANZASTAY pedido comida habitación '.$habitacion->numero
                );
            }

            $cleaningDescription = null;
            $cleaningRequestedTime = null;

            if ($validated['service_type'] === 'limpieza') {
                $cleaningDescription = $description ?? CleaningRequestType::ROOM_CLEANING;
                $cleaningRequestedTime = CleaningRequestType::isAmenity($cleaningDescription)
                    || AmenityRequestType::isHousekeepingAmenity($cleaningDescription)
                    ? null
                    : ($validated['requested_time'] ?? null);
            }

            $order = Order::query()->create([
                'habitacion_id' => $habitacion->id,
                'room_number' => $habitacion->numero,
                'session_token' => $habitacion->current_session_token,
                'guest_email' => $habitacion->guest_email,
                'service_type' => $validated['service_type'],
                'requested_time' => $cleaningRequestedTime,
                'description' => match ($validated['service_type']) {
                    'mantenimiento' => $description,
                    'limpieza' => $cleaningDescription,
                    'comida' => $isRestaurantAmenity ? $description : null,
                    default => null,
                },
                'notas' => $validated['service_type'] === 'comida' ? ($validated['notas'] ?? null) : null,
                'total_price' => $orderTotal,
                'status' => $paidWithCard ? 'pagado' : 'recibido',
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

    public function poll(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'nullable|in:comida,limpieza,mantenimiento',
        ]);

        $serviceType = $validated['service_type'] ?? null;

        $this->authorize('poll', [Order::class, $serviceType]);

        $query = Order::with(['services', 'habitacion'])->latest();

        if ($serviceType === 'limpieza') {
            $query->cleaningBoard();
        } elseif ($serviceType !== null) {
            $query->where('service_type', $serviceType);
        }

        return response()->json([
            'orders' => $query->take(100)->get(),
        ]);
    }

    public function update(Order $order, Request $request)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => 'sometimes|required|in:recibido,en_proceso,en_camino,completado,pagado',
            'requested_time' => 'nullable|date_format:H:i',
        ]);

        $payload = [];

        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }

        if (array_key_exists('requested_time', $validated)) {
            $payload['requested_time'] = $validated['requested_time'];
        }

        if ($payload !== []) {
            $order->update($payload);
        }

        return redirect()->back();
    }

    /**
     * PDF de un pedido finalizado (KDS / cocina).
     */
    public function orderKdsPdf(Order $order)
    {
        $this->authorize('downloadKitchenInvoice', $order);

        if (! in_array($order->status, ['completado', 'entregado'], true)) {
            abort(403);
        }

        $order->load('services');

        return Pdf::loadView('pdf.order-kds', [
            'order' => $order,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')
            ->download('Factura-pedido-'.$order->id.'.pdf');
    }

}
