<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Services;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Lógica de negocio del módulo de Vehículos.
 *
 * Los controladores se limitan a traducir HTTP <-> servicio; toda la lógica
 * vive aquí para poder reutilizarse desde comandos, jobs o tests.
 */
class VehicleService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Vehicle>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, Vehicle>
     */
    public function getForExport(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query($filters)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vehicle
    {
        return Vehicle::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->fill($data)->save();

        return $vehicle->refresh();
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    public function restore(Vehicle $vehicle): Vehicle
    {
        $vehicle->restore();

        return $vehicle->refresh();
    }

    /**
     * Guarda la foto en el disco `public` y reemplaza la anterior si existía.
     */
    public function updatePhoto(Vehicle $vehicle, UploadedFile $file): Vehicle
    {
        $this->deleteStoredPhoto($vehicle);

        $vehicle->photo_path = $file->store('vehicles', 'public');
        $vehicle->save();

        return $vehicle->refresh();
    }

    /**
     * Borra la foto actual del vehículo, si tiene.
     */
    public function deletePhoto(Vehicle $vehicle): Vehicle
    {
        $this->deleteStoredPhoto($vehicle);

        $vehicle->photo_path = null;
        $vehicle->save();

        return $vehicle->refresh();
    }

    private function deleteStoredPhoto(Vehicle $vehicle): void
    {
        if ($vehicle->photo_path !== null) {
            Storage::disk('public')->delete($vehicle->photo_path);
        }
    }

    /**
     * Marcas registradas, para poblar el filtro del listado.
     *
     * @return list<string>
     */
    public function brands(): array
    {
        return Vehicle::query()
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Vehicle>
     */
    protected function query(array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Vehicle::query()
            ->when(! empty($filters['with_trashed']), fn (Builder $q) => $q->withTrashed())
            ->search($filters['search'] ?? null)
            ->when(! empty($filters['type']), fn (Builder $q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['fuel_type']), fn (Builder $q) => $q->where('fuel_type', $filters['fuel_type']))
            ->when(! empty($filters['brand']), fn (Builder $q) => $q->where('brand', $filters['brand']))
            ->when(! empty($filters['year_from']), fn (Builder $q) => $q->where('year', '>=', $filters['year_from']))
            ->when(! empty($filters['year_to']), fn (Builder $q) => $q->where('year', '<=', $filters['year_to']))
            ->when(
                isset($filters['is_active']) && $filters['is_active'] !== null,
                fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active'])
            )
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }
}
