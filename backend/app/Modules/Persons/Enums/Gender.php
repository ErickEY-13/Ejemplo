<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Femenino',
            self::Male => 'Masculino',
            self::Other => 'Otro',
            self::Undisclosed => 'Prefiere no indicarlo',
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
