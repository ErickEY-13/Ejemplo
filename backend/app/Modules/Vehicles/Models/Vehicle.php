<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Models;

use App\Modules\Vehicles\Database\Factories\VehicleFactory;
use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $plate
 * @property string $brand
 * @property string $model
 * @property int $year
 * @property VehicleType $type
 * @property FuelType $fuel_type
 * @property string|null $color
 * @property string|null $vin
 * @property string|null $engine_number
 * @property int $mileage
 * @property bool $is_active
 * @property string|null $notes
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'plate',
        'brand',
        'model',
        'year',
        'type',
        'fuel_type',
        'color',
        'vin',
        'engine_number',
        'mileage',
        'is_active',
        'notes',
    ];

    /**
     * Valores por defecto a nivel de modelo.
     *
     * Duplican los DEFAULT de la migración a propósito: los de la base de datos
     * no se reflejan en la instancia recién creada, y sin esto un `create()` sin
     * `fuel_type` devolvería null donde el recurso espera un enum.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => VehicleType::Car->value,
        'fuel_type' => FuelType::Gasoline->value,
        'mileage' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'fuel_type' => FuelType::class,
            'year' => 'integer',
            'mileage' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }

    // ----------------------------------------------------------- Atributos

    /**
     * La placa siempre se almacena y se devuelve en mayúsculas.
     */
    protected function plate(): Attribute
    {
        return Attribute::set(fn (string $value): string => strtoupper(trim($value)));
    }

    protected function vin(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value !== null && trim($value) !== ''
            ? strtoupper(trim($value))
            : null);
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->brand} {$this->model} ({$this->year})"));
    }

    // ------------------------------------------------------------- Scopes

    /**
     * Busca en placa, marca, modelo y VIN.
     *
     * @param  Builder<Vehicle>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($term)).'%';

        $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(plate) LIKE ?', [$like])
                ->orWhereRaw('LOWER(brand) LIKE ?', [$like])
                ->orWhereRaw('LOWER(model) LIKE ?', [$like])
                ->orWhereRaw("LOWER(brand || ' ' || model) LIKE ?", [$like])
                ->orWhereRaw('LOWER(COALESCE(vin, \'\')) LIKE ?', [$like]);
        });
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
