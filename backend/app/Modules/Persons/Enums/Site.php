<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum Site: string
{
    case Main = 'main';
    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
    case Annex = 'annex';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Sede Central',
            self::North => 'Sede Norte',
            self::South => 'Sede Sur',
            self::East => 'Sede Este',
            self::West => 'Sede Oeste',
            self::Annex => 'Anexo Municipal',
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
