<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Descubre y registra automáticamente todos los módulos de app/Modules.
 *
 * Gracias a esto, añadir un módulo nuevo NO requiere tocar ningún archivo
 * compartido: basta con crear la carpeta con su ServiceProvider dentro.
 * Es la pieza que permite que varios desarrolladores trabajen en paralelo
 * sin generar conflictos en git.
 */
class ModuleRegistrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->discoverModuleProviders() as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * @return list<class-string<ServiceProvider>>
     */
    protected function discoverModuleProviders(): array
    {
        $appPath = str_replace('\\', '/', app_path()).'/';
        $pattern = $appPath.'Modules/*/Providers/*ServiceProvider.php';

        $providers = [];

        foreach (glob($pattern) ?: [] as $file) {
            $relative = substr(str_replace('\\', '/', $file), strlen($appPath), -strlen('.php'));
            $class = 'App\\'.str_replace('/', '\\', $relative);

            if (class_exists($class) && is_subclass_of($class, ServiceProvider::class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        return $providers;
    }
}
