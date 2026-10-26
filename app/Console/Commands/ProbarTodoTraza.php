<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, File};
use Symfony\Component\Process\Process;
use Throwable;

class ProbarTodoTraza extends Command
{
    protected $signature = 'traza:probar-todo {--preparar : Autorizar crear y migrar exclusivamente traza_pruebas si esta vacia}';
    protected $description = 'Ejecuta las cuatro suites de Traza en una base aislada; nunca ejecuta migrate:fresh.';

    public function handle(): int
    {
        if (! app()->environment('local') || app()->configurationIsCached() || config('database.default') !== 'mysql') {
            $this->error('Ejecuta desde APP_ENV=local, conexion mysql y sin config cache (php artisan config:clear).');
            return self::FAILURE;
        }
        $cfg = config('database.connections.mysql');
        if (! is_array($cfg) || ! empty($cfg['url']) || ($cfg['database'] ?? '') === 'traza_pruebas') {
            $this->error('Inicia desde la configuracion local de trabajo, no desde testing, y sin DB_URL. No cambies tu .env.');
            return self::FAILURE;
        }
        foreach (['vendor/phpunit/phpunit/phpunit', 'phpunit.traza.completo.xml', 'tests/Soporte/CasoTraza.php',
            'tests/Feature/AutenticacionTrazaTest.php', 'tests/Feature/OrganizacionTrazaTest.php',
            'tests/Feature/TareasTrazaTest.php', 'tests/Feature/SeguimientoTrazaTest.php'] as $archivo) {
            if (! is_file(base_path($archivo))) {
                $this->error('Falta '.$archivo.'. Conserva las cuatro fases y las dependencias de desarrollo.');
                return self::FAILURE;
            }
        }
        // Solo se permiten las 16 migraciones entregadas. Evita ejecutar ejemplos o migraciones ajenas.
        $nombres = ['roles', 'estados_tarea', 'prioridades', 'tipos_tarea', 'categorias', 'usuarios', 'proyectos',
            'tableros', 'usuarios_tableros', 'columnas_tablero', 'tareas', 'subtareas', 'elementos_verificacion',
            'comentarios_tarea', 'actividades_tarea'];
        $esperadas = [];
        foreach ($nombres as $i => $tabla) $esperadas[] = sprintf('2026_10_04_%06d_crear_tabla_%s.php', $i + 1, $tabla);
        $esperadas[] = '2026_10_04_000016_crear_soporte_recuperacion.php';
        $encontradas = array_map('basename', glob(database_path('migrations/*.php')) ?: []);
        sort($esperadas); sort($encontradas);
        if ($encontradas !== $esperadas) {
            $this->error('Las migraciones no coinciden con el paquete. No se migrara automaticamente. Revisa los archivos, sin borrar datos.');
            return self::FAILURE;
        }
        $dir = storage_path('app/private/entrega'); File::ensureDirectoryExists($dir);
        $id = gmdate('Ymd_His').'_'.bin2hex(random_bytes(3));
        $log = $dir.'/pruebas_'.$id.'.txt'; $junit = $dir.'/pruebas_'.$id.'.xml';
        File::put($log, 'TRAZA - PRUEBAS AISLADAS | '.gmdate('c').PHP_EOL.'Destino exclusivo: traza_pruebas'.PHP_EOL);
        // Las credenciales se transmiten al proceso hijo por entorno, nunca por argumentos o informes.
        $env = [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'APP_DEBUG' => 'false',
            'APP_LOCALE' => 'es', 'APP_FALLBACK_LOCALE' => 'es', 'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'traza_pruebas', 'DB_URL' => '',
            'DB_HOST' => (string) ($cfg['host'] ?? '127.0.0.1'), 'DB_PORT' => (string) ($cfg['port'] ?? 3306),
            'DB_USERNAME' => (string) ($cfg['username'] ?? ''), 'DB_PASSWORD' => (string) ($cfg['password'] ?? ''),
            'DB_SOCKET' => (string) ($cfg['unix_socket'] ?? ''),
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array', 'BCRYPT_ROUNDS' => '4',
        ];
        $resultado = self::FAILURE;
        try {
            $existe = DB::table('information_schema.SCHEMATA')->where('SCHEMA_NAME', 'traza_pruebas')->exists();
            if (! $existe) {
                if (! $this->option('preparar')) {
                    $this->error('No existe traza_pruebas. Usa --preparar para autorizar su creacion aislada.');
                    return self::FAILURE;
                }
                DB::statement('CREATE DATABASE `traza_pruebas` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $this->info('Creada exclusivamente la base traza_pruebas.');
            }
            config(['database.connections.traza_cierre_pruebas' => array_replace($cfg, ['database' => 'traza_pruebas', 'url' => null])]);
            DB::purge('traza_cierre_pruebas');
            $pruebas = DB::connection('traza_cierre_pruebas');
            if (($pruebas->selectOne('SELECT DATABASE() AS nombre')->nombre ?? '') !== 'traza_pruebas') {
                throw new \RuntimeException('La conexion fisica de pruebas no coincide.');
            }
            $tablas = $pruebas->table('information_schema.TABLES')->where('TABLE_SCHEMA', 'traza_pruebas')->pluck('TABLE_NAME')->all();
            if ($tablas === []) {
                if (! $this->option('preparar')) {
                    $this->error('La base de pruebas esta vacia. Usa --preparar para crear solo sus tablas.');
                    return self::FAILURE;
                }
                if ($this->ejecutar([PHP_BINARY, 'artisan', 'traza:destino-pruebas', '--env=testing', '--no-ansi'], $env, $log) !== 0) return self::FAILURE;
                if ($this->ejecutar([PHP_BINARY, 'artisan', 'migrate', '--env=testing', '--no-interaction', '--no-ansi'], $env, $log) !== 0) return self::FAILURE;
            } else {
                $permitidas = array_merge($nombres, ['migrations', 'password_reset_tokens']);
                sort($permitidas); sort($tablas);
                if ($tablas !== $permitidas) {
                    throw new \RuntimeException('traza_pruebas contiene una estructura distinta. No se borra ni se migra sobre ella.');
                }
            }
            // Rechazar una base de pruebas que contenga cuentas o trabajo persistente, aunque tenga el nombre esperado.
            $sinDatos = array_merge(array_diff($nombres, ['roles', 'estados_tarea', 'prioridades', 'tipos_tarea', 'categorias']), ['password_reset_tokens']);
            foreach ($sinDatos as $tabla) {
                if ($pruebas->table($tabla)->exists()) throw new \RuntimeException('traza_pruebas contiene datos en '.$tabla.'. No se ejecutaran pruebas ni se borraran esos datos.');
            }
            if ($this->ejecutar([PHP_BINARY, 'artisan', 'traza:destino-pruebas', '--env=testing', '--no-ansi'], $env, $log) !== 0) return self::FAILURE;
            $this->info('Ejecutando las cuatro suites en traza_pruebas. No se envia correo externo.');
            $resultado = $this->ejecutar([PHP_BINARY, 'vendor/phpunit/phpunit/phpunit', '--configuration=phpunit.traza.completo.xml',
                '--colors=never', '--log-junit='.$junit], $env, $log);
        } catch (\RuntimeException $e) {
            // QueryException es RuntimeException: no volcar SQL/credenciales de errores de conexion al informe.
            $mensaje = $e instanceof \Illuminate\Database\QueryException
                ? 'Fallo de conexion o permiso MySQL. Revisa la conexion local y el permiso para crear traza_pruebas; no modifiques traza_db.'
                : $e->getMessage();
            $this->error($mensaje); File::append($log, PHP_EOL.$mensaje.PHP_EOL);
        } catch (Throwable $e) {
            $mensaje = 'La ejecucion se detuvo ('.get_class($e).'). Revisa el entorno; no se borran bases automaticamente.';
            $this->error($mensaje); File::append($log, PHP_EOL.$mensaje.PHP_EOL);
        } finally {
            DB::disconnect('traza_cierre_pruebas');
            $this->line('Informe local: '.$log);
            if (is_file($junit)) $this->line('Resultados JUnit: '.$junit);
            $this->line('Son pruebas de integracion. Faltan arrastre real, navegador, SMTP y evidencias del curso; no certifican toda la entrega.');
        }
        return $resultado === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function ejecutar(array $comando, array $env, string $log): int
    {
        $proceso = new Process($comando, base_path(), $env, null, 300);
        return $proceso->run(function (string $tipo, string $buffer) use ($log): void {
            $this->output->write($buffer); File::append($log, $buffer);
        });
    }
}
