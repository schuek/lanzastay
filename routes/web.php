<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\OrderController;
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
Route::get('/menu', [ServiceController::class, 'index'])->name('menu');
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

// Admin de Servicios, Pedidos y Habitaciones
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    // Servicios
    Route::get('/', [ServiceController::class, 'admin'])->name('admin.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // GESTION DE PEDIDOS
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/poll', [OrderController::class, 'poll'])->name('orders.poll');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');

    // --- GESTIÓN DE HABITACIONES Y QRs ---
    Route::get('/qrcodes', [ServiceController::class, 'qrcodes'])->name('admin.qrcodes');
    Route::get('/rooms', [ServiceController::class, 'rooms'])->name('rooms.index');
    Route::post('/rooms', [ServiceController::class, 'storeRoom'])->name('rooms.store');
    Route::put('/rooms/{room}', [ServiceController::class, 'updateRoom'])->name('rooms.update');
    Route::post('/rooms/{room}/check-in', [ServiceController::class, 'checkInRoom'])->name('rooms.checkin');
    Route::post('/rooms/{room}/check-out', [ServiceController::class, 'checkOutRoom'])->name('rooms.checkout');
    Route::delete('/rooms/{room}', [ServiceController::class, 'destroyRoom'])->name('rooms.destroy');
});

require __DIR__.'/auth.php';
