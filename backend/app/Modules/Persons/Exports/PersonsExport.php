<?php

declare(strict_types=1);

namespace App\Modules\Persons\Exports;

use App\Modules\Persons\Models\Person;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PersonsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly Collection $persons)
    {
    }

    public function collection(): Collection
    {
        return $this->persons;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Documento',
            'Nombre',
            'Apellidos',
            'Email',
            'Teléfono',
            'Área',
            'Cargo',
            'Estado'
        ];
    }

    /**
     * @param Person $person
     */
    public function map($person): array
    {
        return [
            $person->id,
            $person->document_number,
            $person->first_name,
            $person->last_name,
            $person->email ?? '',
            $person->phone ?? '',
            $person->area?->label() ?? '',
            $person->position ?? '',
            $person->is_active ? 'Activo' : 'Inactivo',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
