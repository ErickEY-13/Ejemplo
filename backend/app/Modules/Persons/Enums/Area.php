<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum Area: string
{
    case MayorsOffice = 'mayors_office';
    case PublicWorks = 'public_works';
    case CitizenSecurity = 'citizen_security';
    case Administration = 'administration';
    case Environment = 'environment';
    case SocialDevelopment = 'social_development';
    case Transportation = 'transportation';
    case PublicCleaning = 'public_cleaning';
    case EducationCulture = 'education_culture';
    case Health = 'health';
    case CivilRegistry = 'civil_registry';
    case Treasury = 'treasury';

    public function label(): string
    {
        return match ($this) {
            self::MayorsOffice => 'Alcaldía',
            self::PublicWorks => 'Obras Públicas',
            self::CitizenSecurity => 'Seguridad Ciudadana',
            self::Administration => 'Administración',
            self::Environment => 'Medio Ambiente',
            self::SocialDevelopment => 'Desarrollo Social',
            self::Transportation => 'Transporte',
            self::PublicCleaning => 'Limpieza Pública',
            self::EducationCulture => 'Educación y Cultura',
            self::Health => 'Salud',
            self::CivilRegistry => 'Registro Civil',
            self::Treasury => 'Tesorería',
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
