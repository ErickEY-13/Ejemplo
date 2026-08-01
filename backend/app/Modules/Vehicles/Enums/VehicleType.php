<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Enums;

enum VehicleType: string
{
    case Car = 'car';
    case Motorcycle = 'motorcycle';
    case Truck = 'truck';
    case Van = 'van';
    case Bus = 'bus';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Automóvil',
            self::Motorcycle => 'Motocicleta',
            self::Truck => 'Camión',
            self::Van => 'Camioneta',
            self::Bus => 'Autobús',
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
