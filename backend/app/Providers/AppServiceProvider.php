<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fuera de producción, Eloquent avisa de lazy loading accidental y de
        // atributos inexistentes: los errores se cazan en desarrollo, no en vivo.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Detrás del proxy/balanceador de DigitalOcean, las URLs deben generarse
        // con el mismo esquema que ve el navegador.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
