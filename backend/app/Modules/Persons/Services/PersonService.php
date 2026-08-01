<?php

declare(strict_types=1);

namespace App\Modules\Persons\Services;

use App\Modules\Persons\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lógica de negocio del módulo de Personas.
 *
 * Los controladores se limitan a traducir HTTP <-> servicio; toda la lógica
 * vive aquí para poder reutilizarse desde comandos, jobs o tests.
 */
class PersonService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Person>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Person
    {
        return Person::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        $person->fill($data)->save();

        return $person->refresh();
    }

    public function delete(Person $person): void
    {
        $person->delete();
    }

    public function restore(Person $person): Person
    {
        $person->restore();

        return $person->refresh();
    }

    /**
     * Sedes registradas, para poblar el formulario (datalist).
     *
     * @return list<string>
     */
    public function sites(): array
    {
        return Person::query()
            ->whereNotNull('site')
            ->select('site')
            ->distinct()
            ->orderBy('site')
            ->pluck('site')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Person>
     */
    protected function query(array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Person::query()
            ->when(! empty($filters['with_trashed']), fn (Builder $q) => $q->withTrashed())
            ->search($filters['search'] ?? null)
            ->when(
                ! empty($filters['document_type']),
                fn (Builder $q) => $q->where('document_type', $filters['document_type'])
            )
            ->when(
                isset($filters['is_active']) && $filters['is_active'] !== null,
                fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active'])
            )
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }
}
