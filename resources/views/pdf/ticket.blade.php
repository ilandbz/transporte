<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $ticket->serie_cpe ? $ticket->serie_cpe.'-'.$ticket->correlativo_cpe : $ticket->uuid_local }}</title>
    <style>
        /* dompdf tiene soporte CSS limitado: evitar flexbox/grid, usar tablas y floats. */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            width: 75mm;
        }
        .container {
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header h2 {
            margin: 0;
            font-size: 14px;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 10px;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.details th, table.details td {
            text-align: left;
            padding: 3px 0;
            vertical-align: top;
        }
        table.details th {
            width: 42%;
            font-weight: bold;
        }
        .codes {
            text-align: center;
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .codes img {
            width: 100px;
            height: 100px;
        }
        .codes p {
            font-size: 9px;
            margin: 4px 0 0;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #555;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>TRANSPORTES SHINHUA</h2>
            <p>{{ $ticket->tipo_documento === 'TICKET_INTERNO' ? 'TICKET DE VIAJE' : 'BOLETA DE VENTA ELECTRÓNICA' }}</p>
            @if($ticket->serie_cpe)
                <p><strong>{{ $ticket->serie_cpe }} - {{ $ticket->correlativo_cpe }}</strong></p>
            @endif
            <p><strong>Ruta:</strong> {{ $ticket->origen_tramo }} - {{ $ticket->destino_tramo }}</p>
            <p><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i') }}</p>
        </div>

        <table class="details">
            <tr>
                <th>Pasajero:</th>
                <td>
                    {{ $ticket->nombre_pasajero ?: 'CLIENTES VARIOS' }}<br>
                    <small>{{ $ticket->dni_pasajero ?: '00000000' }}</small>
                </td>
            </tr>
            <tr>
                <th>Asiento:</th>
                <td>{{ $ticket->numero_asiento }} ({{ ucfirst($ticket->clase) }})</td>
            </tr>
            <tr>
                <th>Método de pago:</th>
                <td style="text-transform: uppercase;">{{ $ticket->metodo_pago }}</td>
            </tr>
            <tr>
                <th>Placa bus:</th>
                <td>{{ $ticket->placa_vehiculo }}</td>
            </tr>
            <tr>
                <th>Total pagado:</th>
                <td><strong>S/ {{ number_format($ticket->precio, 2) }}</strong></td>
            </tr>
        </table>

        <div class="codes">
            <img src="{{ $qrBase64 }}" alt="QR">
            <p>{{ $ticket->uuid_local }}</p>
        </div>

        <div class="footer">
            <p>Conserve este comprobante durante el viaje.</p>
        </div>
    </div>
</body>
</html>