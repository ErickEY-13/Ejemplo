<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Redirige la suite a la base de datos `<nombre>_testing`.
     *
     * No basta con declararlo en phpunit.xml: en Docker las variables de
     * entorno llegan también por $_SERVER, y el repositorio de entorno de
     * Laravel les da prioridad sobre los `<env force="true">` de PHPUnit. Sin
     * esta salvaguarda, `php artisan test` con RefreshDatabase borraría la
     * base de datos de desarrollo.
     *
     * La BD de tests la crea el contenedor `db` al inicializarse
     * (docker/postgres/init/02-testing-database.sh).
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $key = "database.connections.{$connection}.database";
        $database = (string) $app['config']->get($key);

        // SQLite en memoria ya está aislado: no hay nada que proteger.
        if ($database === '' || $database === ':memory:') {
            return $app;
        }

        if (! str_ends_with($database, '_testing')) {
            $app['config']->set($key, $database.'_testing');
        }

        if (! str_ends_with((string) $app['config']->get($key), '_testing')) {
            throw new RuntimeException(
                'Los tests deben ejecutarse contra una base de datos terminada en "_testing".'
            );
        }

        return $app;
    }
}
