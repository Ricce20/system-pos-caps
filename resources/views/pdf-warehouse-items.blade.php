<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Artículos en Almacén</title>
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
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .filter-info {
            background: #E8D6C7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .filter-info p {
            margin: 0;
            font-weight: bold;
            color: #2E2626;
        }
        .warehouse-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .warehouse-header {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #2E2626;
        }
        .warehouse-header h3 {
            margin: 0;
            color: #2E2626;
            font-size: 16px;
        }
        .warehouse-header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .items-table {
            margin-top: 10px;
        }
        .items-table th {
            background: #4a5568;
            font-size: 10px;
        }
        .items-table td {
            font-size: 10px;
        }
        .status-available {
            color: #38a169;
            font-weight: bold;
        }
        .status-unavailable {
            color: #e53e3e;
            font-weight: bold;
        }
        .stock-critical {
            color: #e53e3e;
            font-weight: bold;
        }
        .stock-low {
            color: #d69e2e;
            font-weight: bold;
        }
        .stock-normal {
            color: #38a169;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Artículos en Almacén</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Almacén</p>
    </div>

    <div class="filter-info">
        <p>Filtro aplicado: {{ $filterName }}</p>
        <p>Almacén: {{ $warehouse->name }}</p>
    </div>

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen General</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Artículos</h3>
                <p>{{ $countItems }}</p>
            </div>
            <div class="summary-item">
                <h3>Stock Normal</h3>
                <p>{{ $countNormal }}</p>
            </div>
            <div class="summary-item">
                <h3>Stock Bajo</h3>
                <p>{{ $countLow }}</p>
            </div>
            <div class="summary-item">
                <h3>Stock Crítico</h3>
                <p>{{ $countCritical }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Artículos</h2>
        
        @if($records->count() > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Talla</th>
                    <th>Código</th>
                    <th>Código de Barra</th>
                    <th>Stock</th>
                    <th>Estado Stock</th>
                    <th>Disponible</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $warehouseItem)
                <tr>
                    <td>{{ $warehouseItem->item->id }}</td>
                    <td>{{ $warehouseItem->item->product->name }}</td>
                    <td>{{ $warehouseItem->item->product->brand->name ?? 'N/A' }}</td>
                    <td>{{ $warehouseItem->item->product->category->name ?? 'N/A' }}</td>
                    <td>{{ $warehouseItem->item->size->name ?? 'N/A' }}</td>
                    <td>{{ $warehouseItem->item->code ?? 'N/A' }}</td>
                    <td>
                        @if($warehouseItem->item->barcode)
                            @php
                                $barcodePath = public_path('storage/' . $warehouseItem->item->barcode);
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
                                <span class="barcode-fallback">{{ $warehouseItem->item->barcode }}</span>
                            @endif
                        @else
                            <span class="barcode-fallback">N/A</span>
                        @endif
                    </td>
                    <td class="{{ $warehouseItem->stock <= 10 ? 'stock-critical' : ($warehouseItem->stock <= 40 ? 'stock-low' : 'stock-normal') }}">
                        {{ $warehouseItem->stock }}
                    </td>
                    <td>
                        @if($warehouseItem->stock <= 10)
                            <span class="stock-critical">Crítico</span>
                        @elseif($warehouseItem->stock <= 40)
                            <span class="stock-low">Bajo</span>
                        @else
                            <span class="stock-normal">Normal</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $warehouseItem->is_available ? 'status-available' : 'status-unavailable' }}">
                            {{ $warehouseItem->is_available ? 'Disponible' : 'No Disponible' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">
            No hay artículos que coincidan con el filtro seleccionado
        </p>
        @endif
    </div>

    <div class="footer">
        <p>Este reporte de artículos en almacén fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 