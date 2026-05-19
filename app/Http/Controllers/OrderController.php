<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
                try {
                    broadcast(new OrderCreated($order));
                } catch (\Throwable $e) {
                    Log::warning('OrderCreated broadcast failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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
        } catch (QueryException $e) {
            Log::error('Order store database error', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
            ]);

            $message = $this->friendlyDatabaseErrorMessage($e);

            return response()->json(['message' => $message], $message === 'No se pudo registrar el pedido. Inténtalo de nuevo.' ? 500 : 422);
        } catch (\Throwable $e) {
            Log::error('Order store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'No se pudo registrar el pedido. Inténtalo de nuevo.',
            ], 500);
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

            if (! $isRestaurantAmenity) {
                $this->assertValidFoodCart($validated['cart'] ?? [], (float) ($validated['total'] ?? 0));
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

        return DB::transaction(function () use ($validated, $description) {
            $habitacion = GuestRoomResolver::fromAccessToken(
                $validated['access_token'],
                $validated['session_token'],
            );

            $isScheduledRoomCleaning = $validated['service_type'] === 'limpieza'
                && ! CleaningRequestType::isAmenity($description)
                && ! AmenityRequestType::isHousekeepingAmenity($description);

            if ($isScheduledRoomCleaning && Order::roomHasScheduledRoomCleaningToday($habitacion->id)) {
                throw ValidationException::withMessages([
                    'description' => 'Ya has enviado una petición de limpieza hoy. Podrás solicitar otra mañana.',
                ]);
            }

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

            $order = Order::query()->create(
                $this->buildOrderCreateAttributes(
                    $habitacion,
                    $validated,
                    $description,
                    $cleaningDescription,
                    $cleaningRequestedTime,
                    $isRestaurantAmenity,
                    $orderTotal,
                    $paidWithCard,
                ),
            );

            if ($validated['service_type'] === 'comida' && ! $isRestaurantAmenity) {
                $this->attachCartLines($order, $validated['cart'] ?? []);
            }

            return $order;
        });
    }

    /**
     * @param  array<int, array{id: int, quantity: int, price: float|int|string}>  $cart
     */
    private function assertValidFoodCart(array $cart, float $declaredTotal): void
    {
        if ($cart === []) {
            throw ValidationException::withMessages([
                'cart' => 'Debes añadir al menos un producto para pedir comida.',
            ]);
        }

        $ids = collect($cart)->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $services = Service::query()
            ->whereIn('id', $ids)
            ->get(['id', 'service_type', 'price'])
            ->keyBy('id');

        if ($services->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'cart' => 'Uno o más productos del carrito ya no están disponibles.',
            ]);
        }

        $invalidType = $services->first(fn (Service $service) => $service->service_type !== 'comida');
        if ($invalidType) {
            throw ValidationException::withMessages([
                'cart' => 'El carrito contiene productos que no pertenecen al menú de restaurante.',
            ]);
        }

        $computedTotal = round(
            collect($cart)->sum(function (array $item) use ($services) {
                $service = $services->get((int) $item['id']);

                return (float) $service->price * (int) $item['quantity'];
            }),
            2,
        );
        $declared = round($declaredTotal, 2);

        if (abs($computedTotal - $declared) > 0.02) {
            throw ValidationException::withMessages([
                'total' => 'El total del pedido no coincide con el menú actual. Recarga la página e inténtalo de nuevo.',
            ]);
        }
    }

    /**
     * @param  array<int, array{id: int, quantity: int, price: float|int|string}>  $cart
     */
    private function attachCartLines(Order $order, array $cart): void
    {
        $services = Service::query()
            ->whereIn('id', collect($cart)->pluck('id'))
            ->get(['id', 'price'])
            ->keyBy('id');

        foreach ($cart as $item) {
            $service = $services->get((int) $item['id']);
            $order->services()->attach((int) $item['id'], [
                'quantity' => (int) $item['quantity'],
                'price' => (float) ($service->price ?? $item['price']),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOrderCreateAttributes(
        Habitacion $habitacion,
        array $validated,
        ?string $description,
        ?string $cleaningDescription,
        ?string $cleaningRequestedTime,
        bool $isRestaurantAmenity,
        float $orderTotal,
        bool $paidWithCard,
    ): array {
        $attributes = [
            'room_number' => $habitacion->numero,
            'service_type' => $validated['service_type'],
            'description' => match ($validated['service_type']) {
                'mantenimiento' => $description,
                'limpieza' => $cleaningDescription,
                'comida' => $isRestaurantAmenity ? $description : null,
                default => null,
            },
            'total_price' => $orderTotal,
            'status' => $paidWithCard ? 'pagado' : 'recibido',
        ];

        if (Schema::hasColumn('orders', 'habitacion_id')) {
            $attributes['habitacion_id'] = $habitacion->id;
        }

        if (Schema::hasColumn('orders', 'session_token')) {
            $attributes['session_token'] = $habitacion->current_session_token;
        }

        if (Schema::hasColumn('orders', 'guest_email')) {
            $attributes['guest_email'] = $habitacion->guest_email;
        }

        if (Schema::hasColumn('orders', 'requested_time')) {
            $attributes['requested_time'] = $cleaningRequestedTime;
        }

        if (Schema::hasColumn('orders', 'notas') && $validated['service_type'] === 'comida') {
            $attributes['notas'] = $validated['notas'] ?? null;
        }

        if (Schema::hasColumn('orders', 'prioridad')) {
            $attributes['prioridad'] = Order::PRIORIDAD_MEDIA;
        }

        return $attributes;
    }

    private function friendlyDatabaseErrorMessage(QueryException $e): string
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        if ($code === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
            return 'El servicio no está disponible temporalmente. Contacta con recepción.';
        }

        if ($code === 2002 || str_contains($e->getMessage(), 'Connection refused')) {
            return 'No hay conexión con la base de datos. Inténtalo en unos minutos.';
        }

        if ($code === 1452 || str_contains($e->getMessage(), 'foreign key constraint')) {
            return 'Uno de los datos del pedido ya no es válido. Actualiza el menú e inténtalo de nuevo.';
        }

        if ($code === 1054 || str_contains($e->getMessage(), 'Unknown column')) {
            Log::critical('Order store: migración pendiente en tabla orders', ['error' => $e->getMessage()]);

            return 'El sistema está en mantenimiento. Contacta con recepción.';
        }

        return 'No se pudo registrar el pedido. Inténtalo de nuevo.';
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
