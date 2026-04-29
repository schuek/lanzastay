<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Estancia LanzaStay</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #A64B35; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { font-size: 24px; font-weight: 900; color: #A64B35; }
        .section-title { font-size: 14px; font-weight: 700; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; font-size: 11px; text-transform: uppercase; }
        .totals { margin-top: 18px; }
        .totals p { margin: 4px 0; }
        .grand-total { font-size: 15px; font-weight: 800; color: #A64B35; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LANZASTAY</div>
        <div>Factura de estancia</div>
        <div>Habitación: {{ $room->numero }}</div>
        <div>Email cliente: {{ $room->guest_email }}</div>
        <div>Fecha emisión: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <div class="section-title">Pedidos de comida y servicios</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($order->service_type) }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ number_format((float) $order->total_price, 2, ',', '.') }} EUR</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay pedidos registrados.</td></tr>
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
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservas as $reserva)
                <tr>
                    <td>#{{ $reserva->id }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                    <td>{{ $reserva->titulo_actividad }}</td>
                    <td>{{ $reserva->num_personas }}</td>
                    <td>{{ number_format((float) $reserva->precio_total, 2, ',', '.') }} EUR</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay reservas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <p>Total pedidos: {{ number_format($totalOrders, 2, ',', '.') }} EUR</p>
        <p>Total actividades: {{ number_format($totalReservas, 2, ',', '.') }} EUR</p>
        <p class="grand-total">TOTAL GENERAL: {{ number_format($grandTotal, 2, ',', '.') }} EUR</p>
    </div>
</body>
</html>
