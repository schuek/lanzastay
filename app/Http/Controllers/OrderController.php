<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'room_number' => 'required|string',
            'cart' => 'required|array|min:1',
            'total' => 'required|numeric'
        ]);
        DB::transaction(function () use ($request) {

            $order = Order::create([
                'room_number' => $request->room_number,
                'total_price' => $request->total,
                'status' => 'pending'
            ]);

            foreach ($request->cart as $item) {
                $order->services()->attach($item['id'], [
                    'quantity' => 1,
                    'price' => $item['price']
                ]);
            }
        });

        return redirect()->back()->with('success', 'Pedido creado');
    }

    //--PARA ADMIN--
    public function index()
    {
        $orders = \App\Models\Order::with('services')->latest()->get();

        return \Inertia\Inertia::render('Admin/Orders', [
            'orders' => $orders
        ]);
    }

    // CAMBIAR ESTADO (SERVIR / PENDIENTE)
    public function update(\App\Models\Order $order)
    {
        $newStatus = $order->status === 'pending' ? 'completed' : 'pending';

        $order->update(['status' => $newStatus]);

        return redirect()->back();
    }

}
