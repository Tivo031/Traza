<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComprobarDestinoPruebas extends Command
{
    protected $signature = 'traza:destino-pruebas';
    protected $description = 'Barrera de seguridad para el ejecutor de pruebas aisladas.';

    public function handle(): int
    {
        if (app()->configurationIsCached() || ! app()->environment('testing')
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'traza_pruebas'
            || config('database.connections.mysql.url')) {
            $this->error('Destino de pruebas rechazado. Se exige testing/mysql/traza_pruebas, sin DB_URL ni cache de configuracion.');
            return self::FAILURE;
        }
        $real = DB::connection()->selectOne('SELECT DATABASE() AS nombre');
        if (($real->nombre ?? null) !== 'traza_pruebas') {
            $this->error('La conexion fisica no apunta a traza_pruebas.');
            return self::FAILURE;
        }
        $this->info('Destino comprobado: traza_pruebas. La base de trabajo no se usa para pruebas.');
        return self::SUCCESS;
    }
}
