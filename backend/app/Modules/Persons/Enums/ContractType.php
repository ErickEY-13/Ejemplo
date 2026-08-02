<?php

declare(strict_types=1);

namespace App\Modules\Persons\Enums;

enum ContractType: string
{
    case Appointed = 'appointed';
    case Cas = 'cas';
    case ServiceContract = 'service_contract';
    case Hired = 'hired';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::Appointed => 'Nombrado',
            self::Cas => 'CAS',
            self::ServiceContract => 'Locación de servicios',
            self::Hired => 'Contratado',
            self::Intern => 'Practicante',
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
