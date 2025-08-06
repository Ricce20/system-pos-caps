<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Detalles de Corte de Caja</title>
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
        .cash-register-info {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2E2626;
        }
        .cash-register-info h3 {
            margin: 0 0 10px 0;
            color: #2E2626;
            font-size: 16px;
        }
        .cash-register-info p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Detalles de Corte de Caja</h1>
        <p>Generado el: {{ $fecha }}</p>
        <p>Sistema POS - Gestión de Cajas</p>
    </div>

    @if($minDate && $maxDate)
    <div class="date-range">
        <p>Período: {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}</p>
    </div>
    @endif

    <div class="cash-register-info">
        <h3>Información de la Caja</h3>
        <p><strong>Caja:</strong> {{ $cashRegister->name }}</p>
        <p><strong>Sucursal:</strong> {{ $cashRegister->location->name ?? 'N/A' }}</p>
        <p><strong>Usuario Encargado:</strong> {{ $cashRegister->user->name ?? 'N/A' }}</p>
        <p><strong>Estado:</strong> {{ $cashRegister->is_available ? 'Activa' : 'Inactiva' }}</p>
    </div>

    <div class="summary">
        <h2 style="margin-top: 0; color: #2E2626;">Resumen del Corte</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total de Cortes</h3>
                <p>{{ $cantidad }}</p>
            </div>
            <div class="summary-item">
                <h3>Monto Total Contado</h3>
                <p>${{ number_format($total, 2) }}</p>
            </div>
            <div class="summary-item">
                <h3>Promedio por Corte</h3>
                <p>${{ $cantidad > 0 ? number_format($total / $cantidad, 2) : '0.00' }}</p>
            </div>
            <div class="summary-item">
                <h3>Fecha de Reporte</h3>
                <p>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalle de Cortes de Caja</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Cierre</th>
                    <th>Cantidad Inicial</th>
                    <th>Monto de Cierre</th>
                    <th>Monto Contado</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $detail)
                <tr>
                    <td>{{ $detail->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($detail->start_date)->format('d/m/Y H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($detail->end_date)->format('d/m/Y H:i') }}</td>
                    <td>${{ number_format($detail->starting_quantity, 2) }}</td>
                    <td>${{ number_format($detail->closing_amount, 2) }}</td>
                    <td>${{ number_format($detail->counted_amount, 2) }}</td>
                    <td>${{ number_format($detail->counted_amount - $detail->closing_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este reporte de detalles de corte fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html> 