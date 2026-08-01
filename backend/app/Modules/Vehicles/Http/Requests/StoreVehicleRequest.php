<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

class StoreVehicleRequest extends VehicleRequest
{
    protected function isCreating(): bool
    {
        return true;
    }
}
