<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'storeApi'])->name('api.orders.store');
Route::get('/orders/{order}/status', [OrderController::class, 'statusApi'])->name('api.orders.status');
Route::get('/orders/my', [OrderController::class, 'myOrders'])->name('api.orders.my');
Route::post('/activity-reservations', [ReservationController::class, 'store'])->name('api.activity-reservations.store');
Route::get('/activity-reservations/my', [ReservationController::class, 'myReservations'])->name('api.activity-reservations.my');
