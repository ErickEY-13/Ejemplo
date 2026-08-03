<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Services;

use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Models\VehicleAssignment;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
            ->whereNull('ended_at')
            ->first();
    }

    /**
     * @return Collection<int, VehicleAssignment>
     */
    public function currentAll(?int $siteId = null): Collection
    {
        return VehicleAssignment::query()
            ->whereNull('ended_at')
            ->with(['person', 'site'])
            ->when($siteId !== null, fn (Builder $query) => $query->where('site_id', $siteId))
            ->get();
    }

    /**
     * @return Collection<int, VehicleAssignment>
     */
    public function history(Vehicle $vehicle): Collection
    {
        return VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->with(['person', 'site'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Traslada/(re)asigna el vehículo: cierra la asignación activa (si la
     * hay) y crea una nueva. Es la única forma de escribir en
     * `vehicle_assignments`, así que el historial queda completo por
     * construcción.
     *
     * Locks the Vehicle row (stable anchor) to serialize concurrent assign()
     * calls on the same vehicle. Under PostgreSQL's READ COMMITTED isolation,
     * this prevents the race condition where two concurrent txs both see the
     * same active assignment before either closes it. The vehicle row always
     * exists and never disappears, unlike the active assignment row.
     */
    public function assign(Vehicle $vehicle, int $siteId, ?int $personId, ?string $expectedReturnAt, ?string $notes): VehicleAssignment
    {
        return DB::transaction(function () use ($vehicle, $siteId, $personId, $expectedReturnAt, $notes) {
            // Anchor lock: serializes concurrent assign() calls for the same vehicle
            Vehicle::query()->where('id', $vehicle->id)->lockForUpdate()->first();

            // Close any currently active assignment
            VehicleAssignment::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            return VehicleAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'site_id' => $siteId,
                'person_id' => $personId,
                'assigned_at' => now(),
                'expected_return_at' => $expectedReturnAt,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Cierra la asignación activa del vehículo, si la hay. No borra el
     * historial: solo deja de estar vigente.
     */
    public function unassign(Vehicle $vehicle): void
    {
        $this->current($vehicle)?->update(['ended_at' => now()]);
    }

    /**
     * @return Collection<int, Site>
     */
    public function sites(): Collection
    {
        return Site::query()->orderBy('name')->get();
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
