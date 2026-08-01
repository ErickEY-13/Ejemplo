<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Services;

use App\Modules\Assignments\Models\VehicleAssignment;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;

/**
 * Lógica de negocio del módulo de Assignments.
 *
 * Único módulo que conoce tanto a Vehículos como a Personas; ninguno de los
 * dos debería importar clases del otro directamente.
 */
class AssignmentService
{
    public function current(Vehicle $vehicle): ?VehicleAssignment
    {
        return VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->first();
    }

    /**
     * Asigna o reasigna el vehículo: reemplaza la asignación anterior si
     * existía (no se guarda historial).
     */
    public function assign(Vehicle $vehicle, int $personId, ?string $notes): VehicleAssignment
    {
        return VehicleAssignment::query()->updateOrCreate(
            ['vehicle_id' => $vehicle->id],
            ['person_id' => $personId, 'assigned_at' => now(), 'notes' => $notes],
        );
    }

    public function unassign(Vehicle $vehicle): void
    {
        VehicleAssignment::query()->where('vehicle_id', $vehicle->id)->delete();
    }

    /**
     * Búsqueda liviana de personas activas para el selector del frontend.
     * Vive aquí (y no en Personas) para que Vehículos nunca tenga que
     * importar el servicio de Personas.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPeople(?string $term): array
    {
        return Person::query()
            ->active()
            ->search($term)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'document_number', 'site'])
            ->map(fn (Person $person): array => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'document_number' => $person->document_number,
                'site' => $person->site,
            ])
            ->all();
    }
}
