<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sede física donde puede estar un vehículo. Tabla propia, independiente del
 * enum `App\Modules\Persons\Enums\Site`: los códigos iniciales coinciden a
 * propósito, pero cada uno se administra por separado.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Site extends Model
{
    protected $fillable = ['code', 'name'];
}
