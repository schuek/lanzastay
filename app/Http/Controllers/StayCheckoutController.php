<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Mail\StayCheckoutMail;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\ReservaActividad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class StayCheckoutController extends Controller
{
    public function finalize(Habitacion $room): RedirectResponse
    {
        if (!$room->guest_email) {
            return redirect()->back()->with('error', 'No hay email registrado para esta habitacion.');
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

        $pdfBinary = Pdf::loadView('pdf.stay-checkout', [
            'room' => $room,
            'orders' => $orders,
            'reservas' => $reservas,
            'totalOrders' => $totalOrders,
            'totalReservas' => $totalReservas,
            'grandTotal' => $grandTotal,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->output();

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

    public function checkoutAndDownloadInvoice(Habitacion $room): RedirectResponse
    {
        if ($room->status !== 'ocupada' || !$room->current_session_token) {
            return redirect()->route('rooms.index')->with('error', 'La habitación no tiene una estancia activa para facturar.');
        }

        if (!$room->guest_email) {
            return redirect()->back()->with('error', 'No hay email registrado para el huésped; no se puede enviar la factura.');
        }

        $sessionToken = $room->current_session_token;
        $guestEmail = $room->guest_email;

        $orders = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->with('services')
            ->orderBy('created_at')
            ->get();

        $reservas = ReservaActividad::query()
            ->where('habitacion_id', $room->id)
            ->when($guestEmail, fn ($query) => $query->where('email_cliente', $guestEmail))
            ->orderBy('fecha')
            ->get();

        $totalOrders = (float) $orders->sum('total_price');
        $totalReservas = (float) $reservas->sum('precio_total');
        $grandTotal = $totalOrders + $totalReservas;

        Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->where('status', '!=', 'completado')
            ->update(['status' => 'completado']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'room' => $room,
            'orders' => $orders,
            'reservas' => $reservas,
            'totalOrders' => $totalOrders,
            'totalReservas' => $totalReservas,
            'grandTotal' => $grandTotal,
            'generatedAt' => now(),
            'sessionToken' => $sessionToken,
        ])->setPaper('a4', 'portrait');

        $pdfOutput = $pdf->output();

        Mail::to($room->guest_email)->send(new InvoiceMail($room, $pdfOutput));

        $room->update([
            'status' => 'disponible',
            'current_session_token' => null,
            'guest_email' => null,
        ]);

        return redirect()->back()->with('success', 'Check-out completado y factura enviada al huésped.');
    }
}
