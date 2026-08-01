<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVehiclePhotoRequest extends FormRequest
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
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photo' => 'foto',
        ];
    }
}
