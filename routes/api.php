<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'storeApi'])->name('api.orders.store');
Route::get('/orders/{order}/status', [OrderController::class, 'statusApi'])->name('api.orders.status');
