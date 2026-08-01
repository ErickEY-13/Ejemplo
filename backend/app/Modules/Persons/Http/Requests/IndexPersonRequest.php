<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Persons\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Valida los parámetros de búsqueda/paginación del listado.
 */
class IndexPersonRequest extends FormRequest
{
    public const SORTABLE = ['id', 'first_name', 'last_name', 'document_number', 'birth_date', 'created_at'];

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
            'document_type' => ['nullable', new Enum(DocumentType::class)],
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
            'document_type' => $this->input('document_type'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : null,
            'with_trashed' => $this->boolean('with_trashed'),
            'sort' => $this->input('sort', 'created_at'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }
}
