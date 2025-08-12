<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Entrada de Compra - Folio {{ $entryOrder->id }}</title>
    <style>
        /* Reset básico y tipografía */
        body {
            font-family: Arial, sans-serif;
            margin: 20mm auto;
            padding: 0;
            max-width: 210mm; /* Ancho A4 */
            color: #333;
            background: white;
        }
        /* Encabezado */
        .header {
            text-align: center;
            border-bottom: 2px solid #2E2626;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2E2626;
            margin: 0;
            font-size: 22px;
        }
        .header p {
            color: #666;
            margin: 3px 0;
            font-size: 12px;
        }
        /* Información general */
        .order-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2E2626;
            font-size: 13px;
        }
        .order-info h2 {
            margin-top: 0;
            color: #2E2626;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .detail-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .detail-item strong {
            color: #2E2626;
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
        }
        /* Resumen */
        .summary {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .summary-item {
            text-align: center;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .summary-item h3 {
            margin: 0;
            color: #2E2626;
            font-size: 13px;
        }
        .summary-item p {
            margin-top: 5px;
            font-size: 16px;
            font-weight: bold;
            color: #E8D6C7;
        }
        /* Tabla */
        .table-container {
            margin-top: 15px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #2E2626;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        /* Estados */
        .status-available {
            color: #28a745;
            font-weight: bold;
        }
        .status-unavailable {
            color: #dc3545;
            font-weight: bold;
        }
        /* Footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        /* Imagen código de barras */
        .barcode-image {
            max-width: 90px;
            max-height: 25px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .barcode-fallback {
            color: #666;
            font-size: 10px;
            font-style: italic;
            text-align: center;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Entrada de Compra - Folio {{ $entryOrder->id }}</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Compras</p>
    </div>

    <div class="order-info">
        <h2>Información de la Entrada de Compra</h2>
        <div class="order-details">
            <div class="detail-item">
                <strong>Folio:</strong>
                <span>Folio-{{ $entryOrder->id }}</span>
            </div>
            <div class="detail-item">
                <strong>Fecha de Compra:</strong>
                <span>{{ \Carbon\Carbon::parse($entryOrder->date_order)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="detail-item">
                <strong>Proveedor:</strong>
                <span>{{ $entryOrder->supplier->name }}</span>
            </div>
            <div class="detail-item">
                <strong>Teléfono:</strong>
                <span>{{ $entryOrder->supplier->phone }}</span>
            </div>
            <div class="detail-item">
                <strong>Marca:</strong>
                <span>{{ $entryOrder->supplier->brand->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <strong>Realizado por:</strong>
                <span>{{ $entryOrder->user->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item" style="grid-column: 1 / -1;">
                <strong>Notas:</strong>
                <span>{{ $entryOrder->notes ?? 'Sin notas' }}</span>
            </div>
        </div>
    </div>

    <div class="summary">
        <h2>Resumen de la Compra</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Artículos</h3>
                <p>{{ $totalItems }}</p>
            </div>
            <div class="summary-item">
                <h3>Cantidad Total</h3>
                <p>{{ $totalQuantity }}</p>
            </div>
            <div class="summary-item">
                <h3>Total a Pagar</h3>
                <p>${{ number_format($entryOrder->total, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2>Detalle de Artículos Comprados</h2>

        @if($entryOrder->entryOrderDetail->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Modelo</th>
                    <th>Talla</th>
                    <th>Código</th>
                    <th>Código de Barra</th>
                    <th>Cantidad</th>
                    <th>Desperdicio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entryOrder->entryOrderDetail as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->item->product->name }}</td>
                    <td>{{ $detail->item->product->brand->name ?? 'N/A' }}</td>
                    <td>{{ $detail->item->product->category->name ?? 'N/A' }}</td>
                    <td>{{ $detail->item->product->modelCap->name ?? 'N/A' }}</td>
                    <td>{{ $detail->item->size->name ?? 'N/A' }}</td>
                    <td>{{ $detail->item->code ?? 'N/A' }}</td>
                    <td>
                        @if($detail->item->barcode)
                            @php
                                $barcodePath = public_path('uploads/' . $detail->item->barcode);
                                $barcodeBase64 = '';
                                if (file_exists($barcodePath)) {
                                    $barcodeBase64 = base64_encode(file_get_contents($barcodePath));
                                }
                            @endphp
                            @if($barcodeBase64)
                                <img src="data:image/png;base64,{{ $barcodeBase64 }}" 
                                     alt="Código de Barra" 
                                     class="barcode-image">
                            @else
                                <span class="barcode-fallback">{{ $detail->item->barcode }}</span>
                            @endif
                        @else
                            <span class="barcode-fallback">N/A</span>
                        @endif
                    </td>
                    <td>{{ $detail->quantity }}</td>
                    <td>{{ $detail->amount_of_waste ?? 0 }}</td>
                    <td>${{ number_format($detail->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">
            Esta entrada de compra no tiene artículos asociados
        </p>
        @endif
    </div>

    <div class="total-section">
        <h3>Total de la Compra</h3>
        <p>${{ number_format($entryOrder->total, 2) }}</p>
    </div>

    <div class="footer">
        <p>Este reporte de entrada de compra fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html>
