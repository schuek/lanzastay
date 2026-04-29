<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura de estancia LanzaStay</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 6px;">LanzaStay</h2>
    <p style="margin-top: 0;">Gracias por alojarte con nosotros.</p>
    <p>Adjuntamos el PDF profesional con el desglose de tu estancia.</p>
    <p><strong>Habitación:</strong> {{ $roomNumber }}</p>
    <p><strong>Total:</strong> {{ number_format($total, 2, ',', '.') }} EUR</p>
</body>
</html>
