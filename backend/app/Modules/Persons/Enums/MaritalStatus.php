<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
    case Cohabiting = 'cohabiting';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Soltero/a',
            self::Married => 'Casado/a',
            self::Divorced => 'Divorciado/a',
            self::Widowed => 'Viudo/a',
            self::Cohabiting => 'Conviviente',
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
