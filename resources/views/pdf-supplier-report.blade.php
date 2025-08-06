<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Proveedor</title>
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
        .supplier-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #2E2626;
        }
        .supplier-info h2 {
            margin-top: 0;
            color: #2E2626;
            font-size: 18px;
        }
        .supplier-details {
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
            grid-template-columns: repeat(3, 1fr);
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
        .price-primary {
            color: #2E2626;
            font-weight: bold;
        }
        .price-secondary {
            color: #666;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Proveedor</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Proveedores</p>
    </div>

    <div class="supplier-info">
        <h2>Información del Proveedor</h2>
        <div class="supplier-details">
            <div class="detail-item">
                <strong>Nombre:</strong>
                <span>{{ $supplier->name }}</span>
            </div>
            <div class="detail-item">
                <strong>Teléfono:</strong>
                <span>{{ $supplier->phone }}</span>
            </div>
            <div class="detail-item">
                <strong>Dirección:</strong>
                <span>{{ $supplier->address ?? 'No especificada' }}</span>
            </div>
            <div class="detail-item">
                <strong>Marca:</strong>
                <span>{{ $supplier->brand->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <strong>Estado:</strong>
                <span class="{{ $supplier->is_available ? 'status-available' : 'status-unavailable' }}">
                    {{ $supplier->is_available ? 'Disponible' : 'No Disponible' }}
                </span>
            </div>
            <div class="detail-item">
                <strong>Registrado:</strong>
                <span>{{ \Carbon\Carbon::parse($supplier->created_at)->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen de Artículos</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Artículos</h3>
                <p>{{ $totalItems }}</p>
            </div>
            <div class="summary-item">
                <h3>Artículos Disponibles</h3>
                <p>{{ $availableItems }}</p>
            </div>
            <div class="summary-item">
                <h3>Artículos No Disponibles</h3>
                <p>{{ $unavailableItems }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Artículos del Proveedor</h2>
        
        @if($supplierItems->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Modelo</th>
                    <th>Talla</th>
                    <th>Código</th>
                    <th>Código de Barra</th>
                    <th>Precio Compra</th>
                    <th>Precio Venta</th>
                    <th>Principal</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplierItems as $supplierItem)
                <tr>
                    <td>{{ $supplierItem->item->id }}</td>
                    <td>{{ $supplierItem->item->product->name }}</td>
                    <td>{{ $supplierItem->item->product->brand->name ?? 'N/A' }}</td>
                    <td>{{ $supplierItem->item->product->category->name ?? 'N/A' }}</td>
                    <td>{{ $supplierItem->item->product->modelCap->name ?? 'N/A' }}</td>
                    <td>{{ $supplierItem->item->size->name ?? 'N/A' }}</td>
                    <td>{{ $supplierItem->item->code ?? 'N/A' }}</td>
                    <td>
                        @if($supplierItem->item->barcode)
                            @php
                                $barcodePath = public_path('storage/' . $supplierItem->item->barcode);
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
                                <span class="barcode-fallback">{{ $supplierItem->item->barcode }}</span>
                            @endif
                        @else
                            <span class="barcode-fallback">N/A</span>
                        @endif
                    </td>
                    <td class="{{ $supplierItem->is_primary ? 'price-primary' : 'price-secondary' }}">
                        ${{ number_format($supplierItem->purchase_price ?? 0, 2) }}
                    </td>
                    <td class="{{ $supplierItem->is_primary ? 'price-primary' : 'price-secondary' }}">
                        ${{ number_format($supplierItem->sale_price ?? 0, 2) }}
                    </td>
                    <td>
                        <span class="{{ $supplierItem->is_primary ? 'status-available' : 'status-unavailable' }}">
                            {{ $supplierItem->is_primary ? 'Sí' : 'No' }}
                        </span>
                    </td>
                    <td>
                        <span class="{{ $supplierItem->is_available ? 'status-available' : 'status-unavailable' }}">
                            {{ $supplierItem->is_available ? 'Disponible' : 'No Disponible' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">
            Este proveedor no tiene artículos asociados
        </p>
        @endif
    </div>

    <div class="footer">
        <p>Este reporte de proveedor fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 