<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Providers;

use App\Support\Module\ModuleServiceProvider;

class AssignmentsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Assignments';
    }
}
