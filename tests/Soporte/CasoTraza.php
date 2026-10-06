<?php

namespace Tests\Soporte;

use Illuminate\Foundation\Application;
use Tests\TestCase;

abstract class CasoTraza extends TestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        // Se comprueba antes de iniciar las transacciones de prueba.
        if (! $app->environment('testing') || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'traza_pruebas'
            || config('database.connections.mysql.url')) {
            throw new \RuntimeException('Las pruebas requieren MySQL, DB_DATABASE=traza_pruebas y DB_URL vacio. Nunca uses traza_db.');
        }
        return $app;
    }
}
