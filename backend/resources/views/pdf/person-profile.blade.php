<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Personal - {{ $person->full_name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 14px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #1e3a8a;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #64748b;
            margin: 5px 0 0;
            font-size: 12px;
        }
        .profile-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .profile-section table {
            width: 100%;
        }
        .profile-photo {
            width: 130px;
            text-align: left;
            vertical-align: top;
        }
        .profile-photo img {
            width: 110px;
            height: 110px;
            border-radius: 8px;
            object-fit: cover;
        }
        .profile-photo .no-photo {
            width: 110px;
            height: 110px;
            background: #e2e8f0;
            border-radius: 8px;
            display: inline-block;
            text-align: center;
            line-height: 110px;
            color: #64748b;
            font-weight: bold;
        }
        .profile-details h2 {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 20px;
        }
        .profile-details p {
            margin: 2px 0;
        }
        .section-title {
            background: #f1f5f9;
            color: #0f172a;
            padding: 8px 12px;
            font-weight: bold;
            margin: 0 0 15px;
            border-left: 4px solid #3b82f6;
            font-size: 16px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        table.data-table th {
            width: 35%;
            color: #64748b;
            font-weight: normal;
            font-size: 13px;
        }
        table.data-table td {
            font-weight: 500;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>MUNICIPALIDAD</h1>
        <p>FICHA TÉCNICA DE PERSONAL</p>
        <p>Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="profile-section">
        <tr>
            <td class="profile-photo">
                @if($person->photo_path)
                    <img src="{{ storage_path('app/public/' . $person->photo_path) }}" alt="Foto">
                @else
                    <div class="no-photo">SIN FOTO</div>
                @endif
            </td>
            <td class="profile-details">
                <h2>{{ $person->full_name }}</h2>
                <p><strong>{{ $person->document_type->label() }}:</strong> {{ $person->document_number }}</p>
                <p><strong>Cargo:</strong> {{ $person->position ?? 'No especificado' }}</p>
                <p><strong>Área:</strong> {{ $person->area?->label() ?? 'No especificada' }}</p>
                <p><strong>Estado:</strong> {{ $person->is_active ? 'Activo' : 'Inactivo' }}</p>
            </td>
        </tr>
    </table>

    <div class="section-title">Datos Personales</div>
    <table class="data-table">
        <tr>
            <th>Fecha de nacimiento</th>
            <td>{{ $person->birth_date?->format('d/m/Y') ?? '-' }} ({{ $person->age ?? '-' }} años)</td>
            <th>Género</th>
            <td>{{ $person->gender->label() }}</td>
        </tr>
        <tr>
            <th>Estado Civil</th>
            <td>{{ $person->marital_status?->label() ?? '-' }}</td>
            <th>Nivel Educativo</th>
            <td>{{ $person->education_level?->label() ?? '-' }}</td>
        </tr>
        <tr>
            <th>Número de hijos</th>
            <td colspan="3">{{ $person->children_count ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Datos Laborales</div>
    <table class="data-table">
        <tr>
            <th>Tipo de Contrato</th>
            <td>{{ $person->contract_type?->label() ?? '-' }}</td>
            <th>Fecha de Ingreso</th>
            <td>{{ $person->hire_date?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Sede</th>
            <td>{{ $person->site?->label() ?? '-' }}</td>
            <th>Turno de Trabajo</th>
            <td>{{ $person->work_shift?->label() ?? '-' }}</td>
        </tr>
        <tr>
            <th>Sistema de Pensión</th>
            <td>{{ $person->pension_system?->label() ?? '-' }}</td>
            <th>RUC</th>
            <td>{{ $person->ruc ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Contacto</div>
    <table class="data-table">
        <tr>
            <th>Teléfono</th>
            <td>{{ $person->phone ?? '-' }}</td>
            <th>Correo Electrónico</th>
            <td>{{ $person->email ?? '-' }}</td>
        </tr>
        <tr>
            <th>Dirección</th>
            <td colspan="3">{{ $person->address ?? '-' }}</td>
        </tr>
    </table>

    @if($person->emergency_contact_name || $person->emergency_contact_phone)
    <div class="section-title">Contacto de Emergencia</div>
    <table class="data-table">
        <tr>
            <th>Nombre del Contacto</th>
            <td>{{ $person->emergency_contact_name ?? '-' }}</td>
            <th>Teléfono de Emergencia</th>
            <td>{{ $person->emergency_contact_phone ?? '-' }}</td>
        </tr>
    </table>
    @endif

    <div class="footer">
        Este documento es una impresión generada por el sistema.
    </div>

</body>
</html>
