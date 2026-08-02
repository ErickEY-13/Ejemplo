<?php

declare(strict_types=1);

namespace App\Modules\Persons\Services;

use App\Modules\Persons\Enums\PersonDocumentType;
use App\Modules\Persons\Models\Person;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
     * @return \Illuminate\Database\Eloquent\Collection<int, Person>
     */
    public function getForExport(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query($filters)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Person>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with('media')
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

        return $person->refresh()->load('media');
    }

    public function delete(Person $person): void
    {
        $person->delete();
    }

    public function restore(Person $person): Person
    {
        $person->restore();

        return $person->refresh()->load('media');
    }

    // --------------------------------------------------------------- Foto

    /**
     * Guarda la foto en el disco `public` y reemplaza la anterior si existía.
     */
    public function updatePhoto(Person $person, UploadedFile $file): Person
    {
        $person->addMedia($file)->toMediaCollection('avatar');

        return $person->refresh()->load('media');
    }

    /**
     * Borra la foto actual de la persona, si tiene.
     */
    public function deletePhoto(Person $person): Person
    {
        $person->clearMediaCollection('avatar');

        return $person->refresh()->load('media');
    }

    // --------------------------------------------------------- Documentos

    /**
     * Guarda un documento adjunto en el disco `public`.
     */
    public function addDocument(Person $person, UploadedFile $file, PersonDocumentType $type): Media
    {
        return $person->addMedia($file)
            ->withCustomProperties(['document_type' => $type->value])
            ->toMediaCollection('documents');
    }

    /**
     * Borra un documento adjunto del disco y la base de datos.
     */
    public function removeDocument(Media $document): void
    {
        $document->delete();
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
                ! empty($filters['area']),
                fn (Builder $q) => $q->where('area', $filters['area'])
            )
            ->when(
                ! empty($filters['contract_type']),
                fn (Builder $q) => $q->where('contract_type', $filters['contract_type'])
            )
            ->when(
                isset($filters['is_active']) && $filters['is_active'] !== null,
                fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active'])
            )
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }
}
