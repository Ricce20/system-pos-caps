<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Entrada de Compra - Folio {{ $entryOrder->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2E2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2E2626;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #2E2626;
        }
        .order-info h2 {
            margin-top: 0;
            color: #2E2626;
            font-size: 18px;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .detail-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .detail-item strong {
            color: #2E2626;
            display: block;
            margin-bottom: 5px;
        }
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .summary-item h3 {
            margin: 0;
            color: #2E2626;
            font-size: 14px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 18px;
            font-weight: bold;
            color: #E8D6C7;
        }
        .table-container {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #2E2626;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-size: 12px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        .status-available {
            color: #28a745;
            font-weight: bold;
        }
        .status-unavailable {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .barcode-image {
            max-width: 100px;
            max-height: 30px;
            object-fit: contain;
        }
        .barcode-fallback {
            color: #666;
            font-size: 10px;
            font-style: italic;
        }
        .supplier-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #2E2626;
        }
        .supplier-info h4 {
            margin: 0 0 5px 0;
            color: #2E2626;
            font-size: 14px;
        }
        .supplier-info p {
            margin: 2px 0;
            font-size: 12px;
            color: #666;
        }
        .total-section {
            background: #E8D6C7;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: right;
        }
        .total-section h3 {
            margin: 0;
            color: #2E2626;
            font-size: 18px;
        }
        .total-section p {
            margin: 5px 0 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #2E2626;
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
        <h2 style="margin-top: 0; color: #2E2626;">Resumen de la Compra</h2>
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
                <h3>Desperdicio Total</h3>
                <p>{{ $totalWaste }}</p>
            </div>
            <div class="summary-item">
                <h3>Total a Pagar</h3>
                <p>${{ number_format($entryOrder->total, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Artículos Comprados</h2>
        
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
                                $barcodePath = public_path('storage/' . $detail->item->barcode);
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