<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum EducationLevel: string
{
    case None = 'none';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Technical = 'technical';
    case University = 'university';
    case Postgraduate = 'postgraduate';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin estudios',
            self::Primary => 'Primaria',
            self::Secondary => 'Secundaria',
            self::Technical => 'Técnico',
            self::University => 'Universitario',
            self::Postgraduate => 'Posgrado',
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
