<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class CleaningBoardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewCleaningTasks', Order::class);

        $orders = Order::with('habitacion')
            ->cleaningBoard()
            ->latest()
            ->take(100)
            ->get();

        return Inertia::render('Admin/CleaningBoard', [
            'orders' => $orders,
        ]);
    }
}
