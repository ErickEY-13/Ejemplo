<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Enums;

enum FuelType: string
{
    case Gasoline = 'gasoline';
    case Diesel = 'diesel';
    case Gas = 'gas';
    case Hybrid = 'hybrid';
    case Electric = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::Gasoline => 'Gasolina',
            self::Diesel => 'Diésel',
            self::Gas => 'GLP / GNV',
            self::Hybrid => 'Híbrido',
            self::Electric => 'Eléctrico',
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
