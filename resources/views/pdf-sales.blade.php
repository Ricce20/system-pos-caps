<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Ventas</title>
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
        .date-range {
            background: #E8D6C7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .date-range p {
            margin: 0;
            font-weight: bold;
            color: #2E2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Ventas</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Ventas</p>
    </div>

    @if($minDate && $maxDate)
    <div class="date-range">
        <p>Período: {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}</p>
        <p>Sucursal: {{ $locationName ?? 'Todas las Sucursales' }}</p>
    </div>
    @endif

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen General</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Ventas</h3>
                <p>{{ $cantidad }}</p>
            </div>
            <div class="summary-item">
                <h3>Ingresos Totales</h3>
                <p>${{ number_format($total, 2) }}</p>
            </div>
            <div class="summary-item">
                <h3>Promedio por Venta</h3>
                <p>${{ $cantidad > 0 ? number_format($total / $cantidad, 2) : '0.00' }}</p>
            </div>
            <div class="summary-item">
                <h3>Fecha de Reporte</h3>
                <p>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Ventas</h2>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Empleado</th>
                    <th>Usuario</th>
                    <th>Sucursal</th>
                    <th>Caja</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $sale)
                <tr>
                    <td>Folio-{{ $sale->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->employee->name ?? 'No asignado' }}</td>
                    <td>{{ $sale->user->name ?? 'N/A' }}</td>
                    <td>{{ $sale->location->name ?? 'N/A' }}</td>
                    <td>{{ $sale->cashRegister->name ?? 'N/A' }}</td>
                    <td>${{ number_format($sale->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 