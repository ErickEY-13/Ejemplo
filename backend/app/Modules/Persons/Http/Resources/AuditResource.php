<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Resources;

use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Audit
 */
class AuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            // En un sistema real aquí se enviaría el nombre del usuario que hizo el cambio
            'author_name' => 'Sistema',
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
