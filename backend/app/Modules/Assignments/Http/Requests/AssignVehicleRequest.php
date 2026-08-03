<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignVehicleRequest extends FormRequest
{
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
            'site_id' => [
                'required',
                'integer',
                Rule::exists('sites', 'id'),
            ],
            'person_id' => [
                'nullable',
                'integer',
                Rule::exists('people', 'id')->whereNull('deleted_at'),
            ],
            'assigned_at' => ['nullable', 'date', 'before_or_equal:today'],
            'expected_return_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sede',
            'person_id' => 'persona',
            'assigned_at' => 'fecha de inicio',
            'expected_return_at' => 'devolución prevista',
            'notes' => 'observaciones',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.exists' => 'La persona seleccionada no existe o fue eliminada.',
        ];
    }
}
