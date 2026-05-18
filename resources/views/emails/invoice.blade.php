<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu factura — LanzaStay</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:540px;border-collapse:collapse;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(47,42,38,0.08);border:1px solid rgba(47,42,38,0.08);">
                    <tr>
                        <td style="padding:28px 32px;background-color:#2F2A26;color:#ffffff;">
                            <p style="margin:0;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:#A64B35;font-weight:700;">LanzaStay</p>
                            <h1 style="margin:12px 0 0;font-size:22px;font-weight:600;line-height:1.3;color:#ffffff;">Gracias por tu estancia</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;color:#2F2A26;font-size:16px;line-height:1.65;">
                            <p style="margin:0 0 16px;">Estimado huésped,</p>
                            <p style="margin:0 0 16px;">
                                Desde <strong style="color:#A64B35;">LanzaStay</strong> queremos agradecerte sinceramente por haberte alojado con nosotros.
                                Ha sido un placer tenerte como huésped y esperamos que hayas disfrutado de tu experiencia en Lanzarote.
                            </p>
                            <p style="margin:0 0 16px;">
                                Adjunto a este mensaje encontrarás tu <strong>factura detallada en PDF</strong>
                                (<span style="color:#A64B35;">Factura_LanzaStay.pdf</span>), con el desglose de tu alojamiento
                                @if(isset($orders) && $orders->count() > 0)
                                    y los servicios o pedidos realizados durante tu estancia
                                @endif
                                .
                            </p>
                            @if(isset($room->numero))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:20px 0;border-collapse:collapse;background-color:#fafafa;border-radius:8px;border:1px solid rgba(47,42,38,0.08);">
                                    <tr>
                                        <td style="padding:14px 18px;font-size:14px;color:#2F2A26;">
                                            <span style="display:block;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#A64B35;font-weight:700;margin-bottom:4px;">Resumen</span>
                                            Habitación <strong>{{ $room->numero }}</strong>
                                            @if(isset($orders) && $orders->count() > 0)
                                                · {{ $orders->count() }} {{ $orders->count() === 1 ? 'pedido registrado' : 'pedidos registrados' }}
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <p style="margin:0 0 16px;">
                                Si tienes alguna duda sobre el importe o necesitas una aclaración, no dudes en contactar con recepción.
                                Estaremos encantados de ayudarte.
                            </p>
                            <p style="margin:0 0 4px;">Con nuestros mejores deseos,</p>
                            <p style="margin:0;font-weight:600;color:#2F2A26;">El equipo de LanzaStay</p>
                            <hr style="margin:28px 0;border:none;border-top:1px solid rgba(47,42,38,0.12);">
                            <p style="margin:0;font-size:12px;line-height:1.5;color:rgba(47,42,38,0.55);">
                                Si no visualizas el archivo adjunto, revisa tu carpeta de correo no deseado o escríbenos desde recepción.
                                <br><br>
                                <span style="color:#A64B35;font-weight:600;">LanzaStay Assistant 360°</span> · Lanzarote
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
