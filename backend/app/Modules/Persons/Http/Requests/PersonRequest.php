<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Persons\Enums\Area;
use App\Modules\Persons\Enums\ContractType;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\EducationLevel;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Enums\MaritalStatus;
use App\Modules\Persons\Enums\PensionSystem;
use App\Modules\Persons\Enums\Site;
use App\Modules\Persons\Enums\WorkShift;
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
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'education_level' => ['nullable', new Enum(EducationLevel::class)],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:150',
                Rule::unique('people', 'email')->ignore($this->person()?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'emergency_contact_name' => ['nullable', 'string', 'max:200'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:11', 'regex:/^[0-9]+$/'],
            'pension_system' => ['nullable', new Enum(PensionSystem::class)],
            'site' => ['nullable', new Enum(Site::class)],
            'area' => ['nullable', new Enum(Area::class)],
            'position' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', new Enum(ContractType::class)],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'work_shift' => ['nullable', new Enum(WorkShift::class)],
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
            'marital_status' => 'estado civil',
            'education_level' => 'nivel educativo',
            'children_count' => 'número de hijos',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'emergency_contact_name' => 'contacto de emergencia',
            'emergency_contact_phone' => 'teléfono de emergencia',
            'address' => 'dirección',
            'ruc' => 'RUC',
            'pension_system' => 'sistema de pensión',
            'site' => 'sede',
            'area' => 'área',
            'position' => 'cargo',
            'contract_type' => 'tipo de contrato',
            'hire_date' => 'fecha de ingreso',
            'work_shift' => 'turno de trabajo',
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
            'emergency_contact_phone.regex' => 'El teléfono de emergencia solo admite números, espacios y los signos + - ( ).',
            'ruc.regex' => 'El RUC solo admite números.',
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
