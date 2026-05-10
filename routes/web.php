<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StayCheckoutController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rutas Públicas
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::post('/menu/{numero}/checkout', [OrderController::class, 'checkout'])->name('orders.checkout');
Route::get('/menu/{numero}/success', [OrderController::class, 'checkoutSuccess'])->name('orders.checkout.success');
Route::get('/menu/{numero}/cancel', [OrderController::class, 'checkoutCancel'])->name('orders.checkout.cancel');
Route::get('/guest/welcome', [ServiceController::class, 'welcomeGuest'])->name('guest.welcome');
Route::post('/guest/enter', [ServiceController::class, 'registerGuest'])->name('guest.enter');
Route::get('/menu/{numero}', [MenuController::class, 'show'])->name('menu.show');
Route::get('/tracking/{order}', [OrderController::class, 'tracking'])->name('orders.tracking');

// Rutas Privadas
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin: rutas segmentadas por rol (backend + Inertia nav alineados)
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::middleware(['role:admin,cocina,room_service'])->group(function () {
        Route::get('/orders', [OrderAdminController::class, 'index'])->name('admin.orders');
        Route::get('/orders/poll', [OrderController::class, 'poll'])->name('orders.poll');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    });

    Route::middleware(['role:admin,recepcion,limpieza,mantenimiento'])->group(function () {
        Route::get('/qrcodes', [QrCodeController::class, 'index'])->name('admin.qrcodes');
        Route::get('/rooms', [ServiceController::class, 'rooms'])->name('rooms.index');
        Route::post('/rooms', [ServiceController::class, 'storeRoom'])->name('rooms.store');
        Route::put('/rooms/{room}', [ServiceController::class, 'updateRoom'])->name('rooms.update');
        Route::post('/rooms/{room}/check-in', [ServiceController::class, 'checkInRoom'])->name('rooms.checkin');
        Route::post('/rooms/{room}/check-out', [ServiceController::class, 'checkOutRoom'])->name('rooms.checkout');
        Route::get('/rooms/{room}/check-out-factura', [StayCheckoutController::class, 'checkoutAndDownloadInvoice'])->name('rooms.checkout.invoice');
        Route::post('/rooms/{room}/finalizar-estancia', [StayCheckoutController::class, 'finalize'])->name('rooms.finalize-stay');
        Route::delete('/rooms/{room}', [ServiceController::class, 'destroyRoom'])->name('rooms.destroy');
        Route::get('/habitaciones', [HabitacionController::class, 'index'])->name('habitaciones.index');
    });

    Route::middleware(['role:admin,recepcion'])->group(function () {
        Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/activity-reservations', [ReservationController::class, 'indexAdmin'])->name('activity-reservations.index');
        Route::put('/activity-reservations/{reservation}', [ReservationController::class, 'updateStatus'])->name('activity-reservations.update-status');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/', [ServiceController::class, 'admin'])->name('admin.index');
        Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    });
});

require __DIR__.'/auth.php';
