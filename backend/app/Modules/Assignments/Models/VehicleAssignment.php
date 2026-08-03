<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Models;

use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un vehículo con su sede y, opcionalmente, con la persona
 * responsable de él en un momento dado.
 *
 * Cada fila es un registro histórico inmutable: `ended_at = null` significa
 * que sigue vigente. Reasignar (o trasladar de sede) no modifica la fila
 * activa, la cierra y crea una nueva — así se conserva la trazabilidad
 * completa de dónde estuvo un vehículo y quién lo tuvo a cargo.
 *
 * Este módulo es el único puente entre Vehículos y Personas: ninguno de los
 * dos importa clases del otro, pero ambos son libres de ser referenciados
 * desde aquí.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int|null $site_id
 * @property int|null $person_id
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $expected_return_at
 * @property string|null $notes
 */
class VehicleAssignment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'site_id',
        'person_id',
        'assigned_at',
        'ended_at',
        'expected_return_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
            'expected_return_at' => 'date',
        ];
    }

    /**
     * Sigue vigente (no ha sido cerrada por un traslado o una baja).
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * La devolución prevista ya pasó y la asignación sigue vigente.
     */
    public function isOverdue(): bool
    {
        return $this->isActive()
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
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

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
