<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Exports;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehiclesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly Collection $vehicles)
    {
    }

    public function collection(): Collection
    {
        return $this->vehicles;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Placa',
            'Marca',
            'Modelo',
            'Año',
            'Tipo',
            'Combustible',
            'Color',
            'Kilometraje',
            'Estado',
        ];
    }

    /**
     * @param Vehicle $vehicle
     */
    public function map($vehicle): array
    {
        return [
            $vehicle->id,
            $vehicle->plate,
            $vehicle->brand,
            $vehicle->model,
            $vehicle->year,
            $vehicle->type->label(),
            $vehicle->fuel_type->label(),
            $vehicle->color ?? '',
            $vehicle->mileage,
            $vehicle->is_active ? 'Activo' : 'Inactivo',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
