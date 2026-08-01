<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Reglas compartidas entre el alta y la edición de un vehículo.
 */
abstract class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `true` en creación, `false` en edición (donde los campos son opcionales).
     */
    abstract protected function isCreating(): bool;

    protected function vehicle(): ?Vehicle
    {
        $vehicle = $this->route('vehicle');

        return $vehicle instanceof Vehicle ? $vehicle : null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isCreating() ? 'required' : 'sometimes';
        $id = $this->vehicle()?->getKey();

        return [
            'plate' => [
                $required,
                'string',
                'max:15',
                'regex:/^[A-Z0-9\-]{5,15}$/',
                Rule::unique('vehicles', 'plate')->ignore($id),
            ],
            'brand' => [$required, 'string', 'max:60'],
            'model' => [$required, 'string', 'max:60'],
            'year' => [$required, 'integer', 'min:1900', 'max:'.(((int) date('Y')) + 1)],
            'type' => [$required, new Enum(VehicleType::class)],
            'fuel_type' => ['nullable', new Enum(FuelType::class)],
            'color' => ['nullable', 'string', 'max:40'],
            'vin' => [
                'nullable',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
                Rule::unique('vehicles', 'vin')->ignore($id),
            ],
            'engine_number' => ['nullable', 'string', 'max:40'],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:9999999'],
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
            'plate' => 'placa',
            'brand' => 'marca',
            'model' => 'modelo',
            'year' => 'año',
            'type' => 'tipo de vehículo',
            'fuel_type' => 'combustible',
            'color' => 'color',
            'vin' => 'número de chasis (VIN)',
            'engine_number' => 'número de motor',
            'mileage' => 'kilometraje',
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
            'plate.unique' => 'Ya existe un vehículo registrado con esa placa.',
            'plate.regex' => 'La placa debe tener entre 5 y 15 caracteres (letras, números y guiones).',
            'vin.unique' => 'Ya existe un vehículo registrado con ese VIN.',
            'vin.regex' => 'El VIN debe tener 17 caracteres y no admite las letras I, O ni Q.',
            'vin.size' => 'El VIN debe tener exactamente 17 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('plate'))) {
            $normalized['plate'] = strtoupper(preg_replace('/\s+/', '', $this->input('plate')) ?? '');
        }

        if (is_string($this->input('vin'))) {
            $vin = strtoupper(trim($this->input('vin')));
            $normalized['vin'] = $vin === '' ? null : $vin;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
