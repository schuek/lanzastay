<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #{{ $order->id }} — LanzaStay</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #2F2A26; font-size: 12px; }
        .header { border-bottom: 2px solid #A64B35; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 20px; font-weight: 900; color: #A64B35; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        .right { text-align: right; }
        .total { margin-top: 14px; font-size: 14px; font-weight: 800; color: #A64B35; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">LANZASTAY</div>
        <div>Factura / resumen de pedido</div>
        <div style="color:#6b7280;font-size:11px;">Generada: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>
    <p><strong>Habitación:</strong> {{ $order->room_number }} &nbsp;|&nbsp; <strong>Pedido:</strong> #{{ $order->id }}</p>
    <p><strong>Estado:</strong> {{ $order->status }}</p>

    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="right">Cant.</th>
                <th class="right">P. unit.</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td class="right">{{ $service->pivot->quantity }}</td>
                    <td class="right">{{ number_format((float) $service->pivot->price, 2, ',', '.') }} EUR</td>
                    <td class="right">{{ number_format((float) $service->pivot->price * (int) $service->pivot->quantity, 2, ',', '.') }} EUR</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">{{ $order->service_type === 'limpieza' ? 'Limpieza — '.($order->requested_time ?? '—') : ($order->service_type === 'mantenimiento' ? ($order->description ?? 'Mantenimiento') : 'Sin líneas de detalle.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <p class="total">Total: {{ number_format((float) $order->total_price, 2, ',', '.') }} EUR</p>
</body>
</html>
