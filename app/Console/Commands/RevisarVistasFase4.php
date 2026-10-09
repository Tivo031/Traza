<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class RevisarVistasFase4 extends Command
{
    protected $signature = 'traza:revisar-vistas-fase4';
    protected $description = 'Compila las vistas de la fase 4 en memoria y revisa la sintaxis PHP, sin ejecutar su contenido.';

    public function handle(): int
    {
        $nombres = ['tareas/mostrar', 'seguimiento/version', 'seguimiento/subtareas', 'seguimiento/subtarea',
            'seguimiento/elemento', 'seguimiento/comentarios', 'panel/mostrar', 'proyectos/mostrar', 'tableros/mostrar'];
        $filas = []; $errores = 0;
        foreach ($nombres as $nombre) {
            $ruta = resource_path('views/'.$nombre.'.blade.php');
            if (! is_file($ruta)) { $filas[] = [$nombre, 'Falta el archivo']; $errores++; continue; }
            try {
                $compilado = app('blade.compiler')->compileString(file_get_contents($ruta));
                token_get_all($compilado, TOKEN_PARSE);
                $filas[] = [$nombre, 'Sintaxis correcta'];
            } catch (Throwable $error) {
                $filas[] = [$nombre, $error->getMessage()]; $errores++;
            }
        }
        $this->table(['Vista', 'Resultado'], $filas);
        $this->line('Revision de sintaxis. Falta comprobar renderizado, datos, permisos y acciones en la aplicacion.');
        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
