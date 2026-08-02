<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum PersonDocumentType: string
{
    case Cv = 'cv';
    case Certificate = 'certificate';
    case IdCopy = 'id_copy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cv => 'Currículum vitae',
            self::Certificate => 'Certificado',
            self::IdCopy => 'Copia de documento',
            self::Other => 'Otro',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
