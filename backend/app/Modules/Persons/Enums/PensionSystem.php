<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum PensionSystem: string
{
    case Onp = 'onp';
    case Afp = 'afp';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Onp => 'ONP',
            self::Afp => 'AFP',
            self::None => 'Ninguno',
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
