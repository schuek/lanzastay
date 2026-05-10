<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderAdminController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with(['services', 'habitacion'])->latest()->take(50)->get();

        return Inertia::render('Admin/Orders', [
            'orders' => $orders,
        ]);
    }
}
