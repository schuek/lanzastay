<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Mail\StayCheckoutMail;
use App\Models\Habitacion;
use App\Models\Order;
use App\Models\ActivityReservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StayCheckoutController extends Controller
{
    public function finalize(Habitacion $room): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        if (!$room->guest_email) {
            return redirect()->back()->with('error', 'No hay email registrado para esta habitacion.');
        }

        $orders = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('guest_email', $room->guest_email)
            ->with('services')
            ->orderBy('created_at')
            ->get();

        $reservas = ActivityReservation::query()
            ->with('activity')
            ->where('room_id', $room->id)
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->orderBy('created_at')
            ->get();

        $totalOrders = (float) $orders->sum('total_price');
        $totalReservas = (float) $reservas->sum('total_price');
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
            'check_in_at' => null,
        ]);

        return redirect()->back()->with('success', 'Estancia finalizada y factura enviada por email.');
    }

    public function checkoutAndDownloadInvoice(Habitacion $room): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        return $this->processRoomCheckout($room);
    }

    /**
     * Check-out: genera factura PDF en memoria, la envía por correo y libera la habitación.
     */
    public function processRoomCheckout(Habitacion $room): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        if ($room->status !== 'ocupada' || ! $room->current_session_token) {
            return redirect()->route('rooms.index')->with('error', 'La habitación no tiene una estancia activa para facturar.');
        }

        $emailCliente = $room->guest_email;
        if (! $emailCliente) {
            return redirect()->back()->with('error', 'No hay email registrado para el huésped; no se puede enviar la factura.');
        }

        $sessionToken = $room->current_session_token;
        $viewData = $this->prepareStayInvoiceViewData($room);
        $orders = $viewData['orders'];

        $pdfContent = Pdf::loadView('invoice.template', $viewData)
            ->setPaper('a4', 'portrait')
            ->output();

        Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->where('status', '!=', 'completado')
            ->update(['status' => 'completado']);

        try {
            Mail::to($emailCliente)->send(new InvoiceMail($room, $orders, $pdfContent));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la factura en el check-out', [
                'room_id' => $room->id,
                'email' => $emailCliente,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'No se pudo enviar la factura por correo. El check-out no se ha completado.');
        }

        $room->update([
            'status' => 'disponible',
            'current_session_token' => null,
            'guest_email' => null,
            'check_in_at' => null,
        ]);

        return redirect()->back()->with('success', 'Check-out procesado y la factura ha sido enviada.');
    }

    /**
     * Factura PDF: alojamiento (noches) + pedidos del check-in actual + IGIC.
     */
    public function downloadInvoice(Habitacion $room)
    {
        Gate::authorize('manage-reception-operations');

        $viewData = $this->prepareStayInvoiceViewData($room);

        return Pdf::loadView('invoice.template', $viewData)
            ->setPaper('a4', 'portrait')
            ->download('Factura_LanzaStay_'.$room->id.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareStayInvoiceViewData(Habitacion $room): array
    {
        $precioNoche = (float) ($room->precio_noche ?? config('hotel.precio_noche_default', 95));

        $checkIn = $room->check_in_at
            ? $room->check_in_at->copy()->startOfDay()
            : now()->copy()->startOfDay();

        if ($room->status === 'ocupada' && $room->current_session_token) {
            $firstOrderAt = Order::query()
                ->where('habitacion_id', $room->id)
                ->where('session_token', $room->current_session_token)
                ->min('created_at');

            if (! $room->check_in_at && $firstOrderAt) {
                $checkIn = Carbon::parse($firstOrderAt)->startOfDay();
            }
        }

        $noches = max(1, (int) $checkIn->diffInDays(now()->copy()->startOfDay()));
        $stayCost = round($noches * $precioNoche, 2);

        $ordersQuery = Order::query()
            ->where('habitacion_id', $room->id)
            ->with('services')
            ->orderBy('created_at');

        if ($room->status === 'ocupada' && $room->current_session_token) {
            $ordersQuery->where('session_token', $room->current_session_token);
        }

        if ($room->check_in_at) {
            $ordersQuery->where('created_at', '>=', $room->check_in_at);
        }

        $orders = $ordersQuery->get();
        $ordersTotal = round((float) $orders->sum('total_price'), 2);

        $subtotal = round($stayCost + $ordersTotal, 2);
        $igicPercent = (float) config('hotel.igic_percent', 7);
        $igic = round($subtotal * ($igicPercent / 100), 2);
        $total = round($subtotal + $igic, 2);

        return [
            'room' => $room,
            'orders' => $orders,
            'subtotal' => $subtotal,
            'igic' => $igic,
            'total' => $total,
            'noches' => $noches,
            'stayCost' => $stayCost,
            'ordersTotal' => $ordersTotal,
            'igicPercent' => $igicPercent,
            'precioNoche' => $precioNoche,
            'checkIn' => $checkIn,
            'generatedAt' => now(),
        ];
    }

    /**
     * Factura PDF de la estancia activa: alojamiento (noches × tarifa) + comida cargada a habitación + otros servicios.
     * Query opcional: enviar_email=1 envía copia al email del huésped si existe.
     */
    public function downloadActiveStayInvoice(Request $request, Habitacion $room)
    {
        Gate::authorize('manage-reception-operations');

        if ($room->status !== 'ocupada' || ! $room->current_session_token) {
            abort(404, 'No hay estancia activa en esta habitación.');
        }

        $sessionToken = $room->current_session_token;
        $precioNoche = (float) ($room->precio_noche ?? config('hotel.precio_noche_default', 95));

        $firstOrderAt = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->min('created_at');

        $checkIn = $room->check_in_at
            ? $room->check_in_at->copy()->startOfDay()
            : ($firstOrderAt ? Carbon::parse($firstOrderAt)->startOfDay() : now()->copy()->startOfDay());

        $noches = max(1, (int) $checkIn->diffInDays(now()->copy()->startOfDay()));
        $subtotalAlojamiento = round($noches * $precioNoche, 2);

        $foodOrders = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->where('service_type', 'comida')
            ->where('status', '!=', 'pagado')
            ->orderBy('created_at')
            ->get();

        $foodTotal = (float) $foodOrders->sum('total_price');

        $otherOrders = Order::query()
            ->where('habitacion_id', $room->id)
            ->where('session_token', $sessionToken)
            ->whereIn('service_type', ['limpieza', 'mantenimiento'])
            ->orderBy('created_at')
            ->get();

        $lineas = [];

        $lineas[] = [
            'concepto' => 'Alojamiento — Habitación '.$room->numero.' ('.$noches.' '.($noches === 1 ? 'noche' : 'noches').')',
            'cantidad' => $noches,
            'precio_unitario' => $precioNoche,
            'subtotal' => $subtotalAlojamiento,
        ];

        if ($foodTotal > 0) {
            $lineas[] = [
                'concepto' => 'Pedidos de comida (cargo a habitación)',
                'cantidad' => 1,
                'precio_unitario' => round($foodTotal, 2),
                'subtotal' => round($foodTotal, 2),
            ];
        }

        foreach ($otherOrders as $order) {
            $label = match ($order->service_type) {
                'limpieza' => 'Servicio de limpieza (pedido #'.$order->id.')',
                'mantenimiento' => 'Mantenimiento (pedido #'.$order->id.')',
                default => 'Servicio #'.$order->id,
            };
            $lineas[] = [
                'concepto' => $label,
                'cantidad' => 1,
                'precio_unitario' => (float) $order->total_price,
                'subtotal' => (float) $order->total_price,
            ];
        }

        $baseImponible = (float) collect($lineas)->sum(fn ($r) => (float) ($r['subtotal'] ?? 0));
        $igicPercent = (float) config('hotel.igic_percent', 7);
        $cuotaImpuesto = round($baseImponible * ($igicPercent / 100), 2);
        $totalAPagar = round($baseImponible + $cuotaImpuesto, 2);

        $numeroFactura = 'LS-'.$room->numero.'-'.now()->format('YmdHis');

        $datos = [
            'lineas' => $lineas,
            'numeroFactura' => $numeroFactura,
            'fechaEmision' => now(),
            'cliente' => $room->guest_email ?? 'Huésped',
            'habitacion' => $room,
            'baseImponible' => $baseImponible,
            'porcentajeImpuesto' => $igicPercent,
            'cuotaImpuesto' => $cuotaImpuesto,
            'totalAPagar' => $totalAPagar,
            'tipoImpuesto' => 'IGIC',
        ];

        $pdf = Pdf::loadView('emails.factura', $datos)->setPaper('a4', 'portrait');

        if ($request->boolean('enviar_email') && filled($room->guest_email)) {
            $ordersForEmail = $foodOrders->concat($otherOrders)->values();

            try {
                Mail::to($room->guest_email)->send(new InvoiceMail($room, $ordersForEmail, $pdf->output()));
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar la factura por email', [
                    'room_id' => $room->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $slug = Str::slug($room->numero, '-');
        $slug = $slug !== '' ? $slug : (string) $room->id;

        return $pdf->download('factura-habitacion-'.$slug.'.pdf');
    }
}
