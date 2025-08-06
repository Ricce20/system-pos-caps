<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Transferencia de Almacén</title>
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
        .transfer-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #2E2626;
        }
        .transfer-info h2 {
            margin-top: 0;
            color: #2E2626;
            font-size: 18px;
        }
        .transfer-details {
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
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Transferencia de Almacén</h1>
        <p>Transferencia N° {{ $transferencia->id }}</p>
        <p>Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="transfer-info">
        <h2>Información de la Transferencia</h2>
        <div class="transfer-details">
            <div class="detail-item">
                <strong>Fecha de Creación:</strong>
                <span>{{ \Carbon\Carbon::parse($transferencia->created_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="detail-item">
                <strong>Estado:</strong>
                <span class="
                    @switch($transferencia->status)
                        @case('pending') status-pending @break
                        @case('completed') status-completed @break
                        @case('cancelled') status-cancelled @break
                        @default
                    @endswitch
                ">
                    @switch($transferencia->status)
                        @case('pending') Pendiente @break
                        @case('completed') Completada @break
                        @case('cancelled') Cancelada @break
                        @default {{ ucfirst($transferencia->status) }}
                    @endswitch
                </span>
            </div>
            <div class="detail-item">
                <strong>Almacén Origen:</strong>
                <span>{{ optional($transferencia->sourceWarehouse)->name }}</span>
            </div>
            <div class="detail-item">
                <strong>Almacén Destino:</strong>
                <span>{{ optional($transferencia->destinationWarehouse)->name }}</span>
            </div>
            <div class="detail-item">
                <strong>Creado por:</strong>
                <span>{{ optional($transferencia->user)->name }}</span>
            </div>
            <div class="detail-item">
                <strong>Fecha de Completado/Cancelado:</strong>
                <span>
                    @if($transferencia->completed_at)
                        {{ \Carbon\Carbon::parse($transferencia->completed_at)->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="color: #2E2626;">Detalles de la Transferencia</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Producto</th>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalles as $i => $detalle)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ optional($detalle->item->product)->id }}</td>
                        <td>{{ optional($detalle->item->product)->name }}</td>
                        <td>{{ optional($detalle->item->size)->name }}</td>
                        <td>{{ $detalle->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este reporte de transferencia fue generado automáticamente por el sistema POS</p>
        <p>© {{ date('Y') }} Sistema POS - Todos los derechos reservados</p>
    </div>
</body>
</html>
