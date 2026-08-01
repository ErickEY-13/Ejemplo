<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Valida los parámetros de búsqueda/paginación del listado.
 */
class IndexVehicleRequest extends FormRequest
{
    public const SORTABLE = ['id', 'plate', 'brand', 'model', 'year', 'mileage', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', new Enum(VehicleType::class)],
            'fuel_type' => ['nullable', new Enum(FuelType::class)],
            'brand' => ['nullable', 'string', 'max:60'],
            'year_from' => ['nullable', 'integer', 'min:1900'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'gte:year_from'],
            'is_active' => ['nullable', 'boolean'],
            'with_trashed' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Filtros ya normalizados, listos para el servicio.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->value(),
            'type' => $this->input('type'),
            'fuel_type' => $this->input('fuel_type'),
            'brand' => $this->input('brand'),
            'year_from' => $this->integer('year_from') ?: null,
            'year_to' => $this->integer('year_to') ?: null,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : null,
            'with_trashed' => $this->boolean('with_trashed'),
            'sort' => $this->input('sort', 'created_at'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }
}
