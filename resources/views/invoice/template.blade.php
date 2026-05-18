<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura LanzaStay — Habitación {{ $room->numero }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #2F2A26;
            background: #ffffff;
            line-height: 1.45;
        }
        .page { padding: 36px 40px 48px; }
        .header {
            border-bottom: 3px solid #A64B35;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .header-top {
            width: 100%;
            border-collapse: collapse;
        }
        .brand {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #2F2A26;
        }
        .brand span { color: #A64B35; }
        .meta { text-align: right; font-size: 10px; color: #2F2A26; }
        .meta strong { display: block; font-size: 12px; color: #2F2A26; margin-bottom: 4px; }
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: #A64B35;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 12px 14px;
            background: #fafafa;
            border: 1px solid #e8e6e4;
        }
        .info-grid td + td { border-left: none; }
        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #A64B35;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #2F2A26;
            margin: 20px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #2F2A26;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items thead th {
            background: #2F2A26;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }
        table.items thead th.num { text-align: right; }
        table.items tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e8e6e4;
            vertical-align: top;
        }
        table.items tbody tr:nth-child(even) td { background: #fafafa; }
        table.items tbody td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        table.items .concept { font-weight: 600; color: #2F2A26; }
        table.items .detail { font-size: 10px; color: #6b6560; margin-top: 3px; }
        .totals-wrap { width: 100%; margin-top: 8px; }
        .totals {
            width: 42%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals td {
            padding: 8px 12px;
            border-bottom: 1px solid #e8e6e4;
        }
        .totals td.label { text-align: left; color: #6b6560; }
        .totals td.amount {
            text-align: right;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .totals tr.grand td {
            background: #2F2A26;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            border: none;
            padding: 12px;
        }
        .totals tr.grand td.amount { color: #ffffff; }
        .footer {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 2px solid #A64B35;
            text-align: center;
        }
        .footer-brand {
            font-size: 11px;
            font-weight: bold;
            color: #A64B35;
            letter-spacing: 1px;
        }
        .footer-note {
            margin-top: 6px;
            font-size: 9px;
            color: #6b6560;
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="header">
            <table class="header-top">
                <tr>
                    <td>
                        <div class="brand">LANZA<span>STAY</span></div>
                        <span class="badge">Factura de estancia</span>
                    </td>
                    <td class="meta">
                        <strong>Factura N.º LS-{{ $room->numero }}-{{ $generatedAt->format('Ymd') }}</strong>
                        Emisión: {{ $generatedAt->format('d/m/Y H:i') }}<br>
                        Habitación: {{ $room->numero }}
                    </td>
                </tr>
            </table>
        </header>

        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Huésped</div>
                    <div>{{ $room->guest_email ?? 'Huésped en estancia' }}</div>
                </td>
                <td>
                    <div class="info-label">Estancia</div>
                    <div>
                        Check-in: {{ $checkIn->format('d/m/Y') }}<br>
                        Noches: {{ $noches }} · Tarifa/noche: {{ number_format($precioNoche, 2, ',', '.') }} €
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Detalle de conceptos</div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 52%;">Concepto</th>
                    <th style="width: 12%;" class="num">Cant.</th>
                    <th style="width: 18%;" class="num">P. unit.</th>
                    <th style="width: 18%;" class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="concept">Alojamiento — Habitación {{ $room->numero }}</div>
                        <div class="detail">{{ $noches }} {{ $noches === 1 ? 'noche' : 'noches' }} de estancia</div>
                    </td>
                    <td class="num">{{ $noches }}</td>
                    <td class="num">{{ number_format($precioNoche, 2, ',', '.') }} €</td>
                    <td class="num">{{ number_format($stayCost, 2, ',', '.') }} €</td>
                </tr>

                @forelse($orders as $order)
                    <tr>
                        <td>
                            <div class="concept">
                                Pedido #{{ $order->id }} — {{ ucfirst(str_replace('_', ' ', $order->service_type)) }}
                            </div>
                            <div class="detail">
                                {{ $order->created_at?->format('d/m/Y H:i') }}
                                @if($order->service_type === 'comida' && $order->services->count())
                                    <br>
                                    @foreach($order->services as $service)
                                        {{ $service->pivot->quantity }}× {{ $service->name }}
                                        @if(!$loop->last) · @endif
                                    @endforeach
                                @elseif($order->service_type === 'limpieza' && $order->requested_time)
                                    <br>Limpieza a las {{ $order->requested_time }}
                                @elseif($order->service_type === 'mantenimiento' && $order->description)
                                    <br>{{ Str::limit($order->description, 80) }}
                                @endif
                                @if($order->notas)
                                    <br>Notas: {{ Str::limit($order->notas, 60) }}
                                @endif
                            </div>
                        </td>
                        <td class="num">1</td>
                        <td class="num">{{ number_format((float) $order->total_price, 2, ',', '.') }} €</td>
                        <td class="num">{{ number_format((float) $order->total_price, 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color: #6b6560; font-style: italic;">Sin pedidos adicionales en esta estancia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals-wrap">
            <table class="totals">
                <tr>
                    <td class="label">Base imponible</td>
                    <td class="amount">{{ number_format($subtotal, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td class="label">IGIC ({{ number_format($igicPercent, 0) }}%)</td>
                    <td class="amount">{{ number_format($igic, 2, ',', '.') }} €</td>
                </tr>
                <tr class="grand">
                    <td class="label">Total a pagar</td>
                    <td class="amount">{{ number_format($total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>

        <footer class="footer">
            <div class="footer-brand">LanzaStay Assistant 360°</div>
            <p class="footer-note">Lanzarote · Documento generado automáticamente. Gracias por su estancia.</p>
        </footer>
    </div>
</body>
</html>
