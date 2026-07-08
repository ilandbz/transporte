<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encomienda - {{ $package->qr_code }}</title>
    <!-- JsBarcode and QRCode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            background-color: #f0f0f0;
        }
        
        .container {
            background-color: #fff;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .format-80mm {
            width: 75mm;
            padding: 10px;
            margin: 0 auto;
        }

        .format-a4 {
            width: 190mm;
            min-height: 277mm;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .header p {
            margin: 5px 0 0;
            font-size: 0.9em;
        }

        .details table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }
        
        .details table th, .details table td {
            text-align: left;
            padding: 4px 0;
            vertical-align: top;
        }
        
        .details table th {
            width: 40%;
            font-weight: bold;
        }

        .codes {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .barcode-container {
            width: 100%;
            overflow: hidden;
            display: flex;
            justify-content: center;
        }

        .barcode-container svg {
            max-width: 100%;
            height: auto;
        }

        .qr-container {
            display: flex;
            justify-content: center;
        }

        @media print {
            body {
                background-color: transparent;
            }
            .container {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            
            @page {
                margin: 0;
            }
        }
        
        /* A4 Specific overrides */
        .format-a4 .details table {
            font-size: 1.2em;
        }
        .format-a4 .header h2 {
            font-size: 2.5em;
        }
        .format-a4 .header p {
            font-size: 1.2em;
        }
        .format-a4 .codes {
            margin-top: 30px;
            padding-top: 30px;
        }
    </style>
</head>
<body>

    <div class="container format-{{ $format }}">
        <div class="header">
            <h2>TRANSPORTES SHINHUA</h2>
            <p>Ticket de Encomienda</p>
            <p><strong>Ruta:</strong> {{ $package->trip->route->origen }} - {{ $package->trip->route->destino }}</p>
            <p><strong>Fecha:</strong> {{ $package->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>Estado Pago:</th>
                    <td style="text-transform: uppercase;">{{ $package->estado_pago }}</td>
                </tr>
                <tr>
                    <th>Remitente:</th>
                    <td>{{ $package->remitente_nombre }}<br><small>{{ $package->remitente_dni }}</small></td>
                </tr>
                <tr>
                    <th>Destinatario:</th>
                    <td>{{ $package->destinatario_nombre }}<br><small>{{ $package->destinatario_dni }}</small></td>
                </tr>
                <tr>
                    <th>Descripción:</th>
                    <td>{{ $package->descripcion }}</td>
                </tr>
                <tr>
                    <th>Peso (kg):</th>
                    <td>{{ number_format($package->peso_kg, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Pagado:</th>
                    <td><strong>S/ {{ number_format($package->precio, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="codes">
            <div class="barcode-container">
                <svg id="barcode"></svg>
            </div>
            <div class="qr-container" id="qrcode"></div>
        </div>
    </div>

    <script>
        const qrCodeData = '{{ $package->qr_code }}';
        
        // Render Barcode
        JsBarcode("#barcode", qrCodeData, {
            format: "CODE128",
            width: {{ $format === 'a4' ? 1.5 : 1 }},
            height: {{ $format === 'a4' ? 60 : 35 }},
            displayValue: true,
            fontSize: {{ $format === 'a4' ? 14 : 10 }},
            margin: 0
        });

        // Render QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: qrCodeData,
            width: {{ $format === 'a4' ? 150 : 100 }},
            height: {{ $format === 'a4' ? 150 : 100 }},
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });
        
        // Trigger print once codes are rendered
        setTimeout(() => {
            window.print();
        }, 800);
    </script>
</body>
</html>
