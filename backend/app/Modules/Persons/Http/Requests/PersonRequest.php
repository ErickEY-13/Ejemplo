<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Reglas compartidas entre el alta y la edición de una persona.
 */
abstract class PersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `true` en creación, `false` en edición (donde los campos son opcionales).
     */
    abstract protected function isCreating(): bool;

    protected function person(): ?Person
    {
        $person = $this->route('person');

        return $person instanceof Person ? $person : null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'document_type' => [$required, new Enum(DocumentType::class)],
            'document_number' => [
                $required,
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('people', 'document_number')
                    ->where(fn ($query) => $query->where(
                        'document_type',
                        $this->input('document_type', $this->person()?->document_type->value)
                    ))
                    ->ignore($this->person()?->getKey()),
            ],
            'first_name' => [$required, 'string', 'max:100'],
            'last_name' => [$required, 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['nullable', new Enum(Gender::class)],
            'email' => [
                'nullable',
                'email:rfc',
                'max:150',
                Rule::unique('people', 'email')->ignore($this->person()?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'birth_date' => 'fecha de nacimiento',
            'gender' => 'género',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'site' => 'sede',
            'is_active' => 'estado',
            'notes' => 'observaciones',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.unique' => 'Ya existe una persona registrada con ese documento.',
            'document_number.regex' => 'El número de documento solo admite letras, números y guiones.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
            'phone.regex' => 'El teléfono solo admite números, espacios y los signos + - ( ).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'document_number' => is_string($this->input('document_number'))
                ? strtoupper(trim($this->input('document_number')))
                : null,
            'email' => is_string($this->input('email'))
                ? strtolower(trim($this->input('email')))
                : null,
            'first_name' => is_string($this->input('first_name'))
                ? trim($this->input('first_name'))
                : null,
            'last_name' => is_string($this->input('last_name'))
                ? trim($this->input('last_name'))
                : null,
        ], fn ($value) => $value !== null));
    }
}
