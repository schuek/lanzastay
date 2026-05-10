<?php

use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::post('/reservations', [ReservationController::class, 'store'])->name('api.reservations.store');
Route::get('/orders/{order}/status', [OrderController::class, 'statusApi'])->name('api.orders.status');
Route::get('/orders/my', [OrderController::class, 'myOrders'])->name('api.orders.my');
