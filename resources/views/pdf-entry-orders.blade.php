<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Entradas de Compra</title>
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
        .date-range {
            background: #E8D6C7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .date-range p {
            margin: 0;
            font-weight: bold;
            color: #2E2626;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Entradas de Compra</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Compras</p>
    </div>

    @if($minDate && $maxDate)
    <div class="date-range">
        <p>Período: {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}</p>
    </div>
    @endif

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen General</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Entradas</h3>
                <p>{{ $totalOrders }}</p>
            </div>
            <div class="summary-item">
                <h3>Total Comprado</h3>
                <p>${{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="summary-item">
                <h3>Promedio por Entrada</h3>
                <p>${{ $totalOrders > 0 ? number_format($totalAmount / $totalOrders, 2) : '0.00' }}</p>
            </div>
            <div class="summary-item">
                <h3>Proveedores Únicos</h3>
                <p>{{ $uniqueSuppliers }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Entradas de Compra</h2>
        
        @if($records->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Realizado por</th>
                    <th>Notas</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $entryOrder)
                <tr>
                    <td>Folio-{{ $entryOrder->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($entryOrder->date_order)->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="supplier-info">
                            <h4>{{ $entryOrder->supplier->name }}</h4>
                            <p>Tel: {{ $entryOrder->supplier->phone }}</p>
                            <p>Marca: {{ $entryOrder->supplier->brand->name ?? 'N/A' }}</p>
                        </div>
                    </td>
                    <td>{{ $entryOrder->user->name ?? 'N/A' }}</td>
                    <td>{{ $entryOrder->notes ?? 'Sin notas' }}</td>
                    <td>${{ number_format($entryOrder->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">
            No hay entradas de compra en el período seleccionado
        </p>
        @endif
    </div>

    @if($records->count() > 0)
    <div class="table-container">
        <h2 style="color: #2E2626;">Resumen por Proveedor</h2>
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Entradas</th>
                    <th>Total Comprado</th>
                    <th>Promedio por Entrada</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplierSummary as $supplier)
                <tr>
                    <td>{{ $supplier->supplier_name }}</td>
                    <td>{{ $supplier->order_count }}</td>
                    <td>${{ number_format($supplier->total_amount, 2) }}</td>
                    <td>${{ number_format($supplier->total_amount / $supplier->order_count, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Este reporte de entradas de compra fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 