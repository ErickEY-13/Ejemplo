<?php

declare(strict_types=1);

namespace App\Support\Module;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Clase base de la que hereda el ServiceProvider de cada módulo.
 *
 * Se encarga de todo el "cableado" repetitivo (rutas, migraciones, config,
 * traducciones) para que crear un módulo nuevo consista únicamente en
 * extender esta clase e indicar su nombre.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Nombre del módulo en StudlyCase, tal y como se llama su carpeta
     * dentro de app/Modules (por ejemplo: "Persons").
     */
    abstract protected function name(): string;

    /**
     * Prefijo de las rutas del módulo dentro de /api.
     * Por defecto se deriva del nombre: Persons -> persons.
     */
    protected function routePrefix(): string
    {
        return Str::kebab(Str::plural($this->name()));
    }

    public function register(): void
    {
        $config = $this->modulePath('Config/config.php');

        if (is_file($config)) {
            $this->mergeConfigFrom($config, Str::snake($this->name()));
        }
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerTranslations();
    }

    /**
     * Las rutas del módulo se publican bajo /api/{prefijo} con el grupo de
     * middleware "api". El nombre de ruta también se prefija, de modo que
     * dos módulos nunca colisionan (persons.index vs vehicles.index).
     */
    protected function registerRoutes(): void
    {
        $routes = $this->modulePath('Routes/api.php');

        if (! is_file($routes)) {
            return;
        }

        Route::middleware('api')
            ->prefix('api/'.$this->routePrefix())
            ->name(Str::snake($this->name()).'.')
            ->group($routes);
    }

    protected function registerMigrations(): void
    {
        $migrations = $this->modulePath('Database/Migrations');

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    protected function registerTranslations(): void
    {
        $lang = $this->modulePath('Resources/lang');

        if (is_dir($lang)) {
            $this->loadTranslationsFrom($lang, Str::snake($this->name()));
        }
    }

    /**
     * Ruta absoluta a un archivo o carpeta dentro del módulo.
     */
    protected function modulePath(string $path = ''): string
    {
        return app_path('Modules/'.$this->name().($path !== '' ? '/'.$path : ''));
    }
}
