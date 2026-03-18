<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\OrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');

    // --- GESTIÓN DE HABITACIONES Y QRs ---
    Route::get('/qrcodes', function () {
        // Buscamos las habitaciones reales en MySQL
        $codes = DB::table('habitacions')->get()->map(function ($hab) {
            return [
                'id' => $hab->id,
                'room' => $hab->numero,
                // Genera el código QR dibujado en texto apuntando al menú con el número oculto
                'qr' => (string) QrCode::size(150)->margin(1)->generate(url('/menu?room=' . $hab->numero))
            ];
        });
        return Inertia::render('Admin/QrCodes', ['codes' => $codes]);
    })->name('admin.qrcodes');

    Route::post('/rooms', function (Request $request) {
        // Guarda la nueva habitación en MySQL
        DB::table('habitacions')->insert([
            'numero' => $request->number,
            'activa' => true
        ]);
        return back();
    })->name('rooms.store');

    Route::delete('/rooms/{id}', function ($id) {
        // Borra la habitación de MySQL
        DB::table('habitacions')->where('id', $id)->delete();
        return back();
    })->name('rooms.destroy');
});

// imprimir QR (Ruta antigua, la dejamos por si la usas en otra parte de la app)
Route::get('/generar-qr/{habitacion}', function ($habitacion) {
    $urlDelMenu = "http://localhost:8080/?habitacion=" . $habitacion;
    return QrCode::size(300)->generate($urlDelMenu);
});

require __DIR__.'/auth.php';
