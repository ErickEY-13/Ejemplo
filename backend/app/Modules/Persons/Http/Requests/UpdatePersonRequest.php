<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Requests;

class UpdatePersonRequest extends PersonRequest
{
    protected function isCreating(): bool
    {
        return false;
    }
}
