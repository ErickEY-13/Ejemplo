<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Resources;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Modules\Persons\Enums\PersonDocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class PersonDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeValue = $this->getCustomProperty('document_type');
        $typeEnum = $typeValue ? PersonDocumentType::tryFrom($typeValue) : null;

        return [
            'id' => $this->id,
            'type' => $typeEnum?->value,
            'type_label' => $typeEnum?->label(),
            'original_name' => $this->file_name,
            'file_url' => $this->original_url,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
