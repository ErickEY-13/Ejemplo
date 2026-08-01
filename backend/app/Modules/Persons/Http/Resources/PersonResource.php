<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Resources;

use App\Modules\Persons\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato JSON del módulo de Personas. Cualquier cambio aquí debe reflejarse
 * en la interfaz Person del frontend.
 *
 * @mixin Person
 */
class PersonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'site' => $this->site,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
