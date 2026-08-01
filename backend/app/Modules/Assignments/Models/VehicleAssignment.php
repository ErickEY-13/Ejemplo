<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Models;

use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un vehículo con la persona responsable de él.
 *
 * Este módulo es el único puente entre Vehículos y Personas: ninguno de los
 * dos importa clases del otro, pero ambos son libres de ser referenciados
 * desde aquí.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int $person_id
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property string|null $notes
 */
class VehicleAssignment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'person_id',
        'assigned_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * `withTrashed`: si la persona se elimina más adelante, la asignación
     * sigue mostrando quién es, en vez de romperse.
     *
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class)->withTrashed();
    }
}
