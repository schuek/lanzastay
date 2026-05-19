<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class OrderAdminController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewKitchen', Order::class);

        $orders = Order::with(['services', 'habitacion'])
            ->where('service_type', 'comida')
            ->latest()
            ->take(50)
            ->get();

        return Inertia::render('Admin/Orders', [
            'orders' => $orders,
            'totalServices' => Service::query()->count(),
        ]);
    }
}
