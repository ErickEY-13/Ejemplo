<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Persons\Enums\PersonDocumentType;
use App\Modules\Persons\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UploadPersonDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpeg,jpg,png', 'max:10240'],
            'type' => ['required', new Enum(PersonDocumentType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'type' => 'tipo de documento',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'El archivo no puede pesar más de 10 MB.',
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator): void {
            /** @var Person|null $person */
            $person = $this->route('person');

            if ($person && $person->documents()->count() >= 3) {
                $validator->errors()->add('file', 'Esta persona ya tiene el máximo de 3 documentos adjuntos.');
            }
        });
    }
}
