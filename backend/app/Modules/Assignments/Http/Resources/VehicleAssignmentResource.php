<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Resources;

use App\Modules\Assignments\Models\VehicleAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato JSON del módulo de Assignments. Cualquier cambio aquí debe
 * reflejarse en la interfaz VehicleAssignment del frontend.
 *
 * @mixin VehicleAssignment
 */
class VehicleAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'site' => $this->site !== null ? [
                'id' => $this->site->id,
                'code' => $this->site->code,
                'name' => $this->site->name,
            ] : null,
            'person' => $this->person !== null ? [
                'id' => $this->person->id,
                'full_name' => $this->person->full_name,
                'document_number' => $this->person->document_number,
                'site' => $this->person->site,
                'is_active' => $this->person->is_active,
                'deleted_at' => $this->person->deleted_at?->toIso8601String(),
            ] : null,
            'notes' => $this->notes,
            'assigned_at' => $this->assigned_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'expected_return_at' => $this->expected_return_at?->toDateString(),
            'is_overdue' => $this->isOverdue(),
        ];
    }
}
