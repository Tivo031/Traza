<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarEntorno extends Command
{
    protected $signature = 'traza:verificar-entorno {--base-vacia : Exigir una base sin tablas para la primera instalacion}';
    protected $description = 'Comprueba PHP y MySQL antes de crear las tablas; no modifica datos.';

    public function handle(): int
    {
        if (PHP_VERSION_ID < 80200 || ! str_starts_with(app()->version(), '12.')) {
            $this->error('Este paquete requiere PHP 8.2 o superior y Laravel 12. No cambies versiones a ciegas.');
            return self::FAILURE;
        }
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'dom'] as $extension) {
            if (! extension_loaded($extension)) {
                $this->error('Falta la extension PHP: '.$extension.'. Revisa php --ini.');
                return self::FAILURE;
            }
        }
        if (config('database.default') !== 'mysql') {
            $this->error('Configura DB_CONNECTION=mysql y ejecuta php artisan config:clear.');
            return self::FAILURE;
        }
        if (config('database.connections.mysql.url')) {
            $this->error('DB_URL esta definido y puede sustituir DB_DATABASE. Revisa esa configuracion antes de continuar.');
            return self::FAILURE;
        }
        if (config('app.timezone') !== 'UTC') {
            $this->error('La zona interna de la aplicacion debe ser UTC; la visualizacion local se aplica por separado.');
            return self::FAILURE;
        }
        if (glob(database_path('migrations/0001_01_01_*.php'))) {
            $this->error('Aun estan las migraciones predeterminadas. Apartalas en el respaldo antes de migrar.');
            return self::FAILURE;
        }
        try {
            $datos = DB::selectOne('SELECT VERSION() AS version, DATABASE() AS base, @@session.time_zone AS zona, @@session.sql_mode AS modo');
            $tablas = DB::select('SHOW TABLES');
        } catch (\Throwable $error) {
            $this->error('No se pudo conectar. Revisa servicio, base, puerto, usuario y clave en .env.');
            $this->line('No se modifico ninguna tabla. No compartas tu contrasena.');
            return self::FAILURE;
        }
        $this->table(['Propiedad', 'Valor'], [
            ['Laravel', app()->version()], ['PHP', PHP_VERSION], ['MySQL', $datos->version],
            ['Base seleccionada', $datos->base], ['Zona de la conexion', $datos->zona],
            ['Tablas existentes', count($tablas)],
        ]);
        if (stripos($datos->version, 'MariaDB') !== false) {
            $this->error('El servidor es MariaDB. El diseno corresponde a MySQL: confirma el motor antes de migrar.');
            return self::FAILURE;
        }
        if (version_compare(preg_replace('/[^0-9.].*$/', '', $datos->version), '8.0.16', '<')) {
            $this->error('Se requiere MySQL con CHECK aplicado (8.0.16 o posterior).');
            return self::FAILURE;
        }
        if ($datos->zona !== '+00:00' || ! str_contains($datos->modo, 'STRICT_')) {
            $this->error('La conexion debe trabajar en UTC (+00:00) y modo SQL estricto.');
            return self::FAILURE;
        }
        if ($this->option('base-vacia') && count($tablas) > 0) {
            $this->error('La base no esta vacia. No ejecutes migrate:fresh. Revisa lo existente antes de seguir.');
            return self::FAILURE;
        }
        if (count($tablas) > 0) {
            $this->warn('Ya hay tablas. Revisa migrate:status; este comando no borra ni modifica datos.');
        }
        $this->info('Verificacion terminada. Confirma que la base seleccionada sea la exclusiva de Traza.');
        return self::SUCCESS;
    }
}
