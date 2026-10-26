<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan, DB, File};
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class RevisarCierreTraza extends Command
{
    protected $signature = 'traza:revisar-cierre {--guardar : Guardar el informe en storage/app/private/entrega}';
    protected $description = 'Reune las revisiones existentes y la sintaxis de las vistas. No modifica registros.';

    public function handle(): int
    {
        $texto = 'TRAZA - REVISION TECNICA | '.gmdate('Y-m-d H:i:s').' UTC'.PHP_EOL;
        $filas = []; $errores = 0;
        foreach (['traza:verificar-base', 'traza:verificar-organizacion', 'traza:verificar-tareas', 'traza:verificar-seguimiento'] as $comando) {
            $salida = new BufferedOutput();
            try {
                $codigo = Artisan::call($comando, ['--no-interaction' => true], $salida);
                $detalle = $salida->fetch();
            } catch (Throwable $e) {
                $codigo = 1;
                $detalle = 'No se pudo ejecutar. Revisa la instalacion de las fases y la conexion local. Tipo: '.get_class($e).PHP_EOL;
            }
            $filas[] = [$comando, $codigo === 0 ? 'SIN INCIDENCIAS DETECTADAS' : 'REVISAR'];
            $errores += (int) ($codigo !== 0);
            $texto .= PHP_EOL.'=== '.$comando.' ==='.PHP_EOL.$detalle;
            if ($codigo !== 0) $this->output->write($detalle);
        }
        $vistas = 0; $fallosVista = [];
        foreach (File::allFiles(resource_path('views')) as $archivo) {
            if (! str_ends_with($archivo->getFilename(), '.blade.php')) continue;
            $vistas++;
            try {
                $php = app('blade.compiler')->compileString(File::get($archivo->getPathname()));
                token_get_all($php, TOKEN_PARSE);
            } catch (Throwable $e) {
                $fallosVista[] = $archivo->getRelativePathname().' ('.get_class($e).')';
            }
        }
        $errores += count($fallosVista);
        $filas[] = ['Sintaxis Blade ('.$vistas.' vistas)', $fallosVista ? 'REVISAR' : 'SIN ERRORES DE SINTAXIS'];
        $texto .= PHP_EOL.'Vistas comprobadas: '.$vistas.'. Fallos de sintaxis: '.count($fallosVista).PHP_EOL.implode(PHP_EOL, $fallosVista).PHP_EOL;
        $this->table(['Comprobacion', 'Resultado'], $filas);
        foreach ($fallosVista as $fallo) $this->error($fallo);
        $mailer = (string) config('mail.default');
        $notaCorreo = 'Transporte de correo: '.$mailer.'. Configurado no equivale a entrega comprobada.';
        if (in_array($mailer, ['log', 'array'], true)) {
            $notaCorreo .= ' Este transporte NO envia a una bandeja real. Falta comprobar SMTP para RF-03.';
        }
        $this->warn($notaCorreo); $texto .= PHP_EOL.$notaCorreo.PHP_EOL;
        try {
            $defectos = DB::table('tareas as t')->join('tipos_tarea as k', 'k.id_tipo_tarea', '=', 't.id_tipo_tarea')
                ->where('k.codigo', 'DEFECTO')->count();
            $nota = 'Tarjetas tipo DEFECTO: '.$defectos.'. Su cantidad no demuestra que sean defectos reales ni que tengan evidencia de cierre.';
            $this->line($nota); $texto .= $nota.PHP_EOL;
        } catch (Throwable $e) {
            $errores++; $texto .= 'No fue posible consultar las tarjetas DEFECTO.'.PHP_EOL;
        }
        $limite = 'Esta revision no ejecuta las pruebas funcionales, de permisos, arrastre, concurrencia ni entrega de correo. No marca casos PASO/FALLO.';
        $this->line($limite); $texto .= $limite.PHP_EOL;
        if ($this->option('guardar')) {
            $dir = storage_path('app/private/entrega');
            File::ensureDirectoryExists($dir);
            $ruta = $dir.'/revision_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(3)).'.txt';
            File::put($ruta, $texto);
            $this->info('Informe local: '.$ruta);
        }
        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
