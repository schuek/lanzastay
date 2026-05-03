<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu factura — LanzaStay</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Georgia,'Times New Roman',serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;border-collapse:collapse;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(47,42,38,0.08);border:1px solid rgba(47,42,38,0.08);">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#A64B35 0%,#8f4030 100%);color:#ffffff;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.22em;text-transform:uppercase;opacity:0.92;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">LanzaStay</p>
                            <h1 style="margin:10px 0 0;font-size:22px;font-weight:600;line-height:1.25;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">Tu factura de estancia</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 36px;color:#2F2A26;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:16px;line-height:1.65;">
                            <p style="margin-top:0;">Hola,</p>
                            <p style="margin:0 0 1em;">
                                Esperamos que hayas disfrutado de tu estancia en LanzaStay. Adjuntamos la factura final con el detalle de tus servicios y consumos.
                            </p>
                            @if(isset($room->numero))
                                <p style="margin:1em 0 0;color:#6b7280;">Habitación: <strong style="color:#2F2A26;">{{ $room->numero }}</strong></p>
                            @endif
                            <p style="margin:1.5em 0 0;">¡Vuelve pronto!</p>
                            <p style="margin:0.5em 0 0;color:#2F2A26;">El equipo de LanzaStay</p>
                            <hr style="margin:28px 0;border:none;border-top:1px solid rgba(47,42,38,0.1);">
                            <p style="margin:0;font-size:12px;color:rgba(47,42,38,0.55);">
                                Este mensaje incluye tu factura en PDF (<strong>Factura-LanzaStay.pdf</strong>). Si no ves el archivo, revisa la carpeta de spam o escríbenos desde recepción.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
