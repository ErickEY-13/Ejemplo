<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum WorkShift: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Night = 'night';
    case Rotating = 'rotating';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Mañana',
            self::Afternoon => 'Tarde',
            self::Night => 'Noche',
            self::Rotating => 'Rotativo',
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
