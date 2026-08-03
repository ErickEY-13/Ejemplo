<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Flota</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 10px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; color: #2563eb; text-transform: uppercase; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; color: #0f172a; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { text-align: right; font-size: 9px; color: #64748b; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .badge { padding: 4px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge.active { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge.inactive { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <h1>Reporte General de Flota de Vehículos</h1>

    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="12%">Placa</th>
                <th width="15%">Marca</th>
                <th width="15%">Modelo</th>
                <th width="8%">Año</th>
                <th width="13%">Tipo</th>
                <th width="13%">Combustible</th>
                <th width="11%">Kilometraje</th>
                <th width="8%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->id }}</td>
                <td>{{ $vehicle->plate }}</td>
                <td>{{ $vehicle->brand }}</td>
                <td>{{ $vehicle->model }}</td>
                <td>{{ $vehicle->year }}</td>
                <td>{{ $vehicle->type->label() }}</td>
                <td>{{ $vehicle->fuel_type->label() }}</td>
                <td>{{ number_format($vehicle->mileage, 0, ',', '.') }} km</td>
                <td>
                    @if($vehicle->is_active)
                        <span class="badge active">Activo</span>
                    @else
                        <span class="badge inactive">Inactivo</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i:s') }} - Total de registros: {{ $vehicles->count() }}
    </div>

</body>
</html>
