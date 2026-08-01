<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum DocumentType: string
{
    case NationalId = 'national_id';
    case ForeignerId = 'foreigner_id';
    case Passport = 'passport';
    case DriverLicense = 'driver_license';

    public function label(): string
    {
        return match ($this) {
            self::NationalId => 'Documento de identidad',
            self::ForeignerId => 'Carné de extranjería',
            self::Passport => 'Pasaporte',
            self::DriverLicense => 'Licencia de conducir',
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
     * Representación pensada para poblar los <select> del frontend.
     *
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
