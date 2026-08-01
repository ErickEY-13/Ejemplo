<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Resources;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato JSON del módulo de Vehículos. Cualquier cambio aquí debe reflejarse
 * en la interfaz Vehicle del frontend.
 *
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate' => $this->plate,
            'brand' => $this->brand,
            'model' => $this->model,
            'description' => $this->description,
            'year' => $this->year,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'fuel_type' => $this->fuel_type?->value,
            'fuel_type_label' => $this->fuel_type?->label(),
            'color' => $this->color,
            'photo_url' => $this->photo_url,
            'vin' => $this->vin,
            'engine_number' => $this->engine_number,
            'mileage' => $this->mileage,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
