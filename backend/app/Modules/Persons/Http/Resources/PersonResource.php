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
            'marital_status' => $this->marital_status?->value,
            'marital_status_label' => $this->marital_status?->label(),
            'education_level' => $this->education_level?->value,
            'education_level_label' => $this->education_level?->label(),
            'children_count' => $this->children_count,
            'email' => $this->email,
            'phone' => $this->phone,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'address' => $this->address,
            'ruc' => $this->ruc,
            'pension_system' => $this->pension_system?->value,
            'pension_system_label' => $this->pension_system?->label(),
            'site' => $this->site?->value,
            'site_label' => $this->site?->label(),
            'area' => $this->area?->value,
            'area_label' => $this->area?->label(),
            'position' => $this->position,
            'contract_type' => $this->contract_type?->value,
            'contract_type_label' => $this->contract_type?->label(),
            'hire_date' => $this->hire_date?->toDateString(),
            'work_shift' => $this->work_shift?->value,
            'work_shift_label' => $this->work_shift?->label(),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'photo_url' => $this->photo_url,
            'documents' => $this->when(
                $this->relationLoaded('media'),
                fn () => PersonDocumentResource::collection($this->getMedia('documents'))
            ),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
