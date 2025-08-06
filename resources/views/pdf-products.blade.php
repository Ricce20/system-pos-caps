<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Productos</title>
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
        .product-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .product-header {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #2E2626;
        }
        .product-header h3 {
            margin: 0;
            color: #2E2626;
            font-size: 16px;
        }
        .product-header p {
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
        <h1>Reporte de Productos</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Productos</p>
    </div>

    <div class="filter-info">
        <p>Filtro aplicado: {{ $filterName }}</p>
    </div>

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen General</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Productos</h3>
                <p>{{ $countProducts }}</p>
            </div>
            <div class="summary-item">
                <h3>Productos Disponibles</h3>
                <p>{{ $countAvailable }}</p>
            </div>
            <div class="summary-item">
                <h3>Productos No Disponibles</h3>
                <p>{{ $countUnavailable }}</p>
            </div>
            <div class="summary-item">
                <h3>Total de Articulos</h3>
                <p>{{ $totalItems }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Productos</h2>
        
        @foreach($records as $product)
        <div class="product-section">
            <div class="product-header">
                <h3>{{ $product->name }}</h3>
                <p><strong>ID:</strong> {{ $product->id }} | 
                   <strong>Descripción:</strong> {{ $product->description ?? 'Sin descripción' }} | 
                   <strong>Marca:</strong> {{ $product->brand->name ?? 'N/A' }} | 
                   <strong>Categoría:</strong> {{ $product->category->name ?? 'N/A' }} | 
                   <strong>Modelo:</strong> {{ $product->modelCap->name ?? 'N/A' }} | 
                   <strong>Estado:</strong> 
                   <span class="{{ $product->is_available ? 'status-available' : 'status-unavailable' }}">
                       {{ $product->is_available ? 'Disponible' : 'No Disponible' }}
                   </span>
                </p>
            </div>

                         @if($product->item->count() > 0)
             <table class="items-table">
                 <thead>
                     <tr>
                         <th>ID</th>
                         <th>Código</th>
                         <th>Código de Barra</th>
                         <th>Precio</th>
                         <th>Estado</th>
                     </tr>
                 </thead>
                 <tbody>
                     @foreach($product->item as $item)
                     <tr>
                         <td>{{ $item->id }}</td>
                         <td>{{ $item->code ?? 'N/A' }}</td>
                         <td>
                             @if($item->barcode)
                                 <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('storage/' . $item->barcode))) }}" 
                                      alt="Código de Barra" 
                                      class="barcode-image"
                                      onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                 <span class="barcode-fallback" style="display: none;">{{ $item->barcode }}</span>
                             @else
                                 <span class="barcode-fallback">N/A</span>
                             @endif
                         </td>
                         <td>
                            ${{ number_format($item->supplierItem->where('is_primary',true)->value('sale_price') ?? 0, 2) }}</td>
                         <td>
                             <span class="{{ $item->is_available ? 'status-available' : 'status-unavailable' }}">
                                 {{ $item->is_available ? 'Disponible' : 'No Disponible' }}
                             </span>
                         </td>
                     </tr>
                     @endforeach
                 </tbody>
             </table>
            @else
            <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">
                Este producto no tiene items asociados
            </p>
            @endif
        </div>
        @endforeach
    </div>

    <div class="footer">
        <p>Este reporte de productos fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 