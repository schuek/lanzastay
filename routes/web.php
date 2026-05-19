<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\CleaningBoardController;
use App\Http\Controllers\Admin\MaintenanceBoardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PersonalController;
use App\Http\Controllers\Admin\ServiceRequestsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StayCheckoutController;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\Service;
use App\Support\UserRole;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/chat', [ChatbotController::class, 'sendMessage'])->name('chat.send');
Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/guest/welcome/{habitacion:access_token}', [ServiceController::class, 'welcomeGuest'])->name('guest.welcome');
Route::post('/guest/enter', [ServiceController::class, 'registerGuest'])->name('guest.enter');
Route::get('/menu/{habitacion:access_token}', [MenuController::class, 'show'])->name('menu.show');
Route::get('/tracking/{order}', [OrderController::class, 'tracking'])->name('orders.tracking');

/*
|--------------------------------------------------------------------------
| Panel y perfil (todos los roles autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $departmentStats = null;
        $operationalKpis = null;

        if ($user && in_array($user->role, [UserRole::ADMIN, UserRole::RECEPCION], true)) {
            $activeStatuses = ['recibido', 'en_proceso', 'en_camino', 'pagado'];
            $kitchenPending = Order::query()
                ->where('service_type', 'comida')
                ->whereIn('status', $activeStatuses)
                ->count();
            $cleaningPending = Order::query()
                ->cleaningBoard()
                ->whereIn('status', $activeStatuses)
                ->count();
            $maintenancePending = Order::query()
                ->where('service_type', 'mantenimiento')
                ->whereIn('status', $activeStatuses)
                ->count();

            $departmentStats = [
                'kitchen' => ['pending' => $kitchenPending],
                'cleaning' => ['pending' => $cleaningPending],
                'maintenance' => ['pending' => $maintenancePending],
            ];

            $roomsBase = Habitacion::query()->where('activa', true);
            $totalRooms = (clone $roomsBase)->count();
            $occupiedRooms = (clone $roomsBase)->where('status', 'ocupada')->count();
            $availableRooms = (clone $roomsBase)->where('status', 'disponible')->count();

            $operationalKpis = [
                'occupancy' => [
                    'occupied' => $occupiedRooms,
                    'total' => $totalRooms,
                    'available' => $availableRooms,
                    'label' => "{$occupiedRooms} / {$totalRooms}",
                    'subtitle' => ($totalRooms === 1 ? 'Habitación' : 'Habitaciones').' en inventario activo',
                ],
                'kitchen_pending' => $kitchenPending,
                'cleaning_pending' => $cleaningPending,
                'maintenance_pending' => $maintenancePending,
                'services_pending' => $cleaningPending + $maintenancePending,
            ];
        }

        $cleaningOrders = null;
        $maintenanceOrders = null;
        $kitchenOrders = null;
        $kitchenStats = null;

        if (in_array($user?->role, [UserRole::COCINA, UserRole::ROOM_SERVICE], true)) {
            $kitchenOrders = Order::with(['services', 'habitacion'])
                ->where('service_type', 'comida')
                ->whereNotIn('status', ['completado', 'entregado'])
                ->latest()
                ->take(30)
                ->get();

            $kitchenStats = [
                'catalogDishes' => Service::query()->where('service_type', 'comida')->count(),
                'ordersToday' => Order::query()
                    ->where('service_type', 'comida')
                    ->whereDate('created_at', today())
                    ->count(),
            ];
        }

        if ($user?->role === UserRole::LIMPIEZA) {
            $cleaningOrders = Order::with('habitacion')
                ->cleaningBoard()
                ->latest()
                ->take(100)
                ->get();
        }

        if ($user?->role === UserRole::MANTENIMIENTO) {
            $maintenanceOrders = Order::with('habitacion')
                ->where('service_type', 'mantenimiento')
                ->latest()
                ->take(100)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'departmentStats' => $departmentStats,
            'operationalKpis' => $operationalKpis,
            'cleaningOrders' => $cleaningOrders,
            'maintenanceOrders' => $maintenanceOrders,
            'kitchenOrders' => $kitchenOrders,
            'kitchenStats' => $kitchenStats,
        ]);
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| RBAC — área de administración (/admin)
|--------------------------------------------------------------------------
| Admin: acceso total (middleware role incluye bypass en EnsureUserHasRole).
| Recepción: operaciones de front-desk, sin catálogo ni gestión de personal.
| Cocina: pedidos (comida) + catálogo.
| Limpieza / Mantenimiento: solo sus tableros y actualización de sus avisos.
*/
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {

    // Cocina — cola de pedidos (lectura)
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::COCINA.','.UserRole::ROOM_SERVICE])
        ->group(function () {
            Route::get('/orders', [OrderAdminController::class, 'index'])->name('orders.kitchen');
            Route::get('/orders/{order}/factura', [OrderController::class, 'orderKdsPdf'])->name('orders.kitchen.invoice');
        });

    // Cocina — actualización de pedidos de comida
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::COCINA.','.UserRole::ROOM_SERVICE])
        ->group(function () {
            Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.kitchen.update');
        });

    // Polling (autorización fina en OrderPolicy::poll)
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::COCINA.','.UserRole::ROOM_SERVICE.','.UserRole::LIMPIEZA.','.UserRole::MANTENIMIENTO])
        ->get('/orders/poll', [OrderController::class, 'poll'])
        ->name('orders.poll');

    // Limpieza — tablero (recepción: solo lectura vía policy en update)
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::LIMPIEZA])
        ->get('/cleaning', [CleaningBoardController::class, 'index'])
        ->name('tasks.cleaning');

    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::LIMPIEZA])
        ->put('/tasks/cleaning/{order}', [OrderController::class, 'update'])
        ->name('tasks.cleaning.update');

    // Mantenimiento — tablero
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::MANTENIMIENTO])
        ->get('/maintenance', [MaintenanceBoardController::class, 'index'])
        ->name('tasks.maintenance');

    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::MANTENIMIENTO])
        ->post('/tasks/maintenance', [MaintenanceBoardController::class, 'store'])
        ->name('tasks.maintenance.store');

    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION.','.UserRole::MANTENIMIENTO])
        ->put('/tasks/maintenance/{order}', [MaintenanceBoardController::class, 'update'])
        ->name('tasks.maintenance.update');

    // Recepción — habitaciones, QR, actividades
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::RECEPCION])
        ->group(function () {
            Route::get('/qrcodes', [QrCodeController::class, 'index'])->name('admin.qrcodes');
            Route::get('/rooms', [ServiceController::class, 'rooms'])->name('rooms.index');
            Route::post('/rooms', [ServiceController::class, 'storeRoom'])->name('rooms.store');
            Route::put('/rooms/{room:id}', [ServiceController::class, 'updateRoom'])->name('rooms.update');
            Route::post('/rooms/{room:id}/check-in', [ServiceController::class, 'checkInRoom'])->name('rooms.checkin');
            Route::post('/rooms/{room:id}/check-out', [ServiceController::class, 'checkOutRoom'])->name('rooms.checkout');
            Route::get('/rooms/{room:id}/factura', [StayCheckoutController::class, 'downloadInvoice'])->name('rooms.invoice.download');
            Route::get('/rooms/{room:id}/factura-estancia', [StayCheckoutController::class, 'downloadActiveStayInvoice'])->name('rooms.invoice.active');
            Route::get('/rooms/{room:id}/check-out-factura', [StayCheckoutController::class, 'checkoutAndDownloadInvoice'])->name('rooms.checkout.invoice');
            Route::post('/rooms/{room:id}/finalizar-estancia', [StayCheckoutController::class, 'finalize'])->name('rooms.finalize-stay');
            Route::delete('/rooms/{room:id}', [ServiceController::class, 'destroyRoom'])->name('rooms.destroy');
            // Alias legado: misma vista que rooms.index (evita 404 en enlaces antiguos)
            Route::get('/habitaciones', fn () => redirect()->route('rooms.index'))->name('habitaciones.index');

            Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
            Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
            Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
            Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

            Route::get('/activity-reservations', [ReservationController::class, 'indexAdmin'])->name('activity-reservations.index');
            Route::put('/activity-reservations/{reservation}', [ReservationController::class, 'updateStatus'])->name('activity-reservations.update-status');

            Route::get('/service-requests', [ServiceRequestsController::class, 'index'])->name('admin.service-requests');
            Route::put('/service-requests/{order}', [ServiceRequestsController::class, 'updateStatus'])->name('admin.service-requests.update');
        });

    // Gestión de personal — Gate manage-users (solo admin)
    Route::middleware(['role:'.UserRole::ADMIN])
        ->group(function () {
            Route::get('/personal', [PersonalController::class, 'index'])->name('admin.personal');
            Route::post('/personal', [PersonalController::class, 'store'])->name('admin.personal.store');
            Route::put('/personal/{user}', [PersonalController::class, 'update'])->name('admin.personal.update');
            Route::delete('/personal/{user}', [PersonalController::class, 'destroy'])->name('admin.personal.destroy');
        });

    // Catálogo — solo admin y cocina (catalog.edit)
    Route::middleware(['role:'.UserRole::ADMIN.','.UserRole::COCINA])
        ->group(function () {
            Route::get('/', [ServiceController::class, 'admin'])->name('catalog.index');
            Route::get('/services/create', [ServiceController::class, 'create'])->name('catalog.create');
            Route::post('/services', [ServiceController::class, 'store'])->name('catalog.store');
            Route::get('/services/{service}', [ServiceController::class, 'show'])->name('catalog.show');
            Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('catalog.edit');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('catalog.update');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('catalog.destroy');
        });
});

require __DIR__.'/auth.php';
