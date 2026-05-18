@php
    $lineas = $lineas ?? $items ?? [];
    $fechaEmision = isset($fechaEmision) ? \Illuminate\Support\Carbon::parse($fechaEmision) : now();
    $baseImponible = isset($baseImponible)
        ? (float) $baseImponible
        : (float) collect($lineas)->sum(fn ($r) => (float) ($r['subtotal'] ?? data_get($r, 'subtotal', 0)));
    $porcentajeImpuesto = isset($porcentajeImpuesto) ? (float) $porcentajeImpuesto : 7;
    $cuotaImpuesto = isset($cuotaImpuesto)
        ? (float) $cuotaImpuesto
        : round($baseImponible * ($porcentajeImpuesto / 100), 2);
    $totalAPagar = isset($totalAPagar)
        ? (float) $totalAPagar
        : round($baseImponible + $cuotaImpuesto, 2);
    $tipoImpuesto = $tipoImpuesto ?? 'IGIC';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura — LanzaStay</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 2rem 1.25rem;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #2F2A26;
            background: #ffffff;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
            border: 2px solid #2F2A26;
            border-radius: 4px;
            overflow: hidden;
            background: #ffffff;
        }
        .header {
            padding: 2rem 1.75rem;
            border-bottom: 2px solid #2F2A26;
            text-align: center;
        }
        .brand {
            margin: 0;
            font-size: 2.25rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            color: #2F2A26;
        }
        .brand span { color: #A64B35; }
        .hotel-meta {
            margin: 0.75rem 0 0;
            font-size: 0.9375rem;
            color: #2F2A26;
            opacity: 0.88;
        }
        .hotel-meta strong { font-weight: 600; }
        .meta-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #2F2A26;
            font-size: 0.8125rem;
        }
        .meta-row dl { margin: 0; }
        .meta-row dt { font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.06em; color: #2F2A26; opacity: 0.75; }
        .meta-row dd { margin: 0.25rem 0 0; font-weight: 600; }
        .section-title {
            margin: 0;
            padding: 0.75rem 1.75rem;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #ffffff;
            background: #2F2A26;
        }
        table.invoice {
            width: 100%;
            border-collapse: collapse;
        }
        table.invoice th,
        table.invoice td {
            padding: 0.75rem 1rem;
            text-align: left;
            border: 1px solid #2F2A26;
        }
        table.invoice thead th {
            background: #fafafa;
            font-size: 0.6875rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        table.invoice tbody td { vertical-align: top; }
        table.invoice .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        table.invoice .concept { font-weight: 600; }
        .totals {
            margin: 0;
            padding: 1.5rem 1.75rem 2rem;
            border-top: 2px solid #2F2A26;
            background: #ffffff;
        }
        .total-box {
            margin-left: auto;
            max-width: 320px;
            border: 2px solid #2F2A26;
            padding: 1.25rem 1.5rem;
            background: #fafafa;
        }
        .total-box .row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .total-box .row:last-child { margin-bottom: 0; }
        .total-box .grand {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #2F2A26;
            font-size: 1.125rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .footer-note {
            padding: 1rem 1.75rem 1.5rem;
            font-size: 0.75rem;
            color: #2F2A26;
            opacity: 0.65;
            border-top: 1px solid #2F2A26;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="header">
            <h1 class="brand">LANZA<span>STAY</span></h1>
            <p class="hotel-meta">
                <strong>Lanzarote</strong>, Islas Canarias, España<br>
                Factura de alojamiento y servicios
            </p>
        </header>

        <div class="meta-row">
            <dl>
                <dt>Factura</dt>
                <dd>{{ $numeroFactura ?? '—' }}</dd>
            </dl>
            <dl>
                <dt>Fecha</dt>
                <dd>{{ $fechaEmision->format('d/m/Y') }}</dd>
            </dl>
            <dl>
                <dt>Cliente</dt>
                <dd>{{ $cliente ?? $guestName ?? '—' }}</dd>
            </dl>
            @isset($habitacion)
                <dl>
                    <dt>Habitación</dt>
                    <dd>{{ is_object($habitacion) ? ($habitacion->numero ?? $habitacion->number ?? '—') : $habitacion }}</dd>
                </dl>
            @endisset
        </div>

        <h2 class="section-title">Detalle de conceptos</h2>
        <table class="invoice" role="table">
            <thead>
                <tr>
                    <th scope="col">Concepto</th>
                    <th scope="col" class="num">Cantidad</th>
                    <th scope="col" class="num">Precio unitario</th>
                    <th scope="col" class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lineas as $fila)
                    @php
                        $c = $fila['concepto'] ?? $fila->concepto ?? '—';
                        $q = $fila['cantidad'] ?? $fila->cantidad ?? 1;
                        $pu = (float) ($fila['precio_unitario'] ?? $fila->precio_unitario ?? 0);
                        $st = isset($fila['subtotal']) || isset($fila->subtotal)
                            ? (float) ($fila['subtotal'] ?? $fila->subtotal)
                            : round($q * $pu, 2);
                    @endphp
                    <tr>
                        <td class="concept">{{ $c }}</td>
                        <td class="num">{{ number_format((float) $q, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($pu, 2, ',', '.') }} €</td>
                        <td class="num">{{ number_format($st, 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td class="concept">Habitación</td>
                        <td class="num">1</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                    </tr>
                    <tr>
                        <td class="concept">Pedidos de comida</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                    </tr>
                    <tr>
                        <td class="concept">Servicios</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="total-box">
                <div class="row">
                    <span>Base imponible</span>
                    <span>{{ number_format($baseImponible, 2, ',', '.') }} €</span>
                </div>
                <div class="row">
                    <span>{{ $tipoImpuesto }} ({{ number_format($porcentajeImpuesto, 0, ',', '.') }}%)</span>
                    <span>{{ number_format($cuotaImpuesto, 2, ',', '.') }} €</span>
                </div>
                <div class="row grand">
                    <span>Total a pagar</span>
                    <span>{{ number_format($totalAPagar, 2, ',', '.') }} €</span>
                </div>
                <p style="margin: 0.75rem 0 0; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.8;">
                    Importe con impuestos incluidos
                </p>
            </div>
        </div>

        <p class="footer-note">
            LanzaStay · Documento generado con fines informativos. Para consultas: recepción del hotel.
        </p>
    </div>
</body>
</html>
