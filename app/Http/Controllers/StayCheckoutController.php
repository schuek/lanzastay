<?php

namespace App\Http\Controllers;

use App\Mail\StayCheckoutMail;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\ReservaActividad;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class StayCheckoutController extends Controller
{
    public function finalize(Habitacion $room): RedirectResponse
    {
        if (!$room->guest_email) {
            return redirect()->back()->with('error', 'No hay email registrado para esta habitacion.');
        }

        if (!class_exists(Dompdf::class)) {
            return redirect()->back()->with('error', 'Falta instalar dompdf. Ejecuta: composer require barryvdh/laravel-dompdf');
        }

        $orders = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('guest_email', $room->guest_email)
            ->with('services')
            ->orderBy('created_at')
            ->get();

        $reservas = ReservaActividad::query()
            ->where('habitacion_id', $room->id)
            ->where('email_cliente', $room->guest_email)
            ->orderBy('fecha')
            ->get();

        $totalOrders = (float) $orders->sum('total_price');
        $totalReservas = (float) $reservas->sum('precio_total');
        $grandTotal = $totalOrders + $totalReservas;

        $pdfHtml = view('pdf.stay-checkout', [
            'room' => $room,
            'orders' => $orders,
            'reservas' => $reservas,
            'totalOrders' => $totalOrders,
            'totalReservas' => $totalReservas,
            'grandTotal' => $grandTotal,
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfBinary = $dompdf->output();

        Mail::to($room->guest_email)->send(new StayCheckoutMail(
            pdfContent: $pdfBinary,
            roomNumber: $room->numero,
            total: $grandTotal,
        ));

        $room->update([
            'status' => 'disponible',
            'current_session_token' => null,
            'guest_email' => null,
        ]);

        return redirect()->back()->with('success', 'Estancia finalizada y factura enviada por email.');
    }
}
