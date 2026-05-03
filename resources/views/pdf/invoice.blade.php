<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura LanzaStay</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #A64B35; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { font-size: 24px; font-weight: 900; color: #A64B35; letter-spacing: 0.4px; }
        .muted { color: #6b7280; }
        .section-title { margin: 16px 0 8px; font-size: 13px; font-weight: 700; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f9fafb; color: #6b7280; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        .totals { margin-top: 18px; width: 50%; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .grand { font-weight: 800; color: #A64B35; font-size: 15px; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 4px; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 999px; background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">LANZASTAY</div>
        <div>Factura de estancia</div>
        <div class="muted">Generada: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <tr>
            <th>Habitación</th>
            <th>Email huésped</th>
            <th>Sesión</th>
        </tr>
        <tr>
            <td>{{ $room->numero }}</td>
            <td>{{ $room->guest_email ?? '-' }}</td>
            <td>{{ substr($sessionToken, 0, 10) }}...</td>
        </tr>
    </table>

    <div class="section-title">Consumos y servicios solicitados</div>
    <table>
        <thead>
            <tr>
                <th># Pedido</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Detalle</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td><span class="pill">{{ ucfirst(str_replace('_', ' ', $order->service_type)) }}</span></td>
                    <td>
                        @if($order->service_type === 'comida' && $order->services->count())
                            @foreach($order->services as $service)
                                {{ $service->pivot->quantity }}x {{ $service->name }} ({{ number_format((float) $service->pivot->price, 2, ',', '.') }} EUR)<br>
                            @endforeach
                        @elseif($order->service_type === 'limpieza')
                            Limpieza solicitada a las {{ $order->requested_time ?? '--:--' }}
                        @elseif($order->service_type === 'mantenimiento')
                            {{ $order->description ?: 'Solicitud de mantenimiento' }}
                        @else
                            Solicitud registrada
                        @endif
                    </td>
                    <td class="right">{{ number_format((float) $order->total_price, 2, ',', '.') }} EUR</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay pedidos asociados a esta sesión.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Reservas de actividades</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Actividad</th>
                <th>Personas</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservas as $reserva)
                <tr>
                    <td>#{{ $reserva->id }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                    <td>{{ $reserva->titulo_actividad }}</td>
                    <td>{{ $reserva->num_personas }}</td>
                    <td class="right">{{ number_format((float) $reserva->precio_total, 2, ',', '.') }} EUR</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin reservas de actividades.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row"><span>Total pedidos:</span><span>{{ number_format($totalOrders, 2, ',', '.') }} EUR</span></div>
        <div class="totals-row"><span>Total actividades:</span><span>{{ number_format($totalReservas, 2, ',', '.') }} EUR</span></div>
        <div class="totals-row grand"><span>Total a pagar:</span><span>{{ number_format($grandTotal, 2, ',', '.') }} EUR</span></div>
    </div>
</body>
</html>
